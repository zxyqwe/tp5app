<?php

namespace app\hanbj\controller;

use Exception;
use hanbj\ActivityOper;
use hanbj\BonusOper;
use hanbj\FameOper;
use hanbj\FeeOper;
use hanbj\MemberOper;
use hanbj\weixin\HanbjPayConfig;
use hanbj\weixin\WxTemp;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\response\Json;
use util\GeneralRet;
use util\MysqlLog;
use wxsdk\pay\WxPayApi;
use wxsdk\pay\WxPayRefund;
use hanbj\SubscribeOper;
use hanbj\PayoutOper;

class Rpcv2 extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            throw new HttpResponseException(json(GeneralRet::EMPTY_POST_BODY()));
        }
        $ts = intval(input('get.ts'));
        if (abs(time() - $ts) > 1800) {
            throw new HttpResponseException(json(GeneralRet::TS_DIFF_HUGE()));
        }
        $sign = $GLOBALS['HTTP_RAW_POST_DATA'] . config('hanbj_rpc_sk') . $ts;
        $sign = md5($sign);
        $post_sign = input('get.sign');
        if ($post_sign !== $sign) {
            throw new HttpResponseException(json(GeneralRet::SIGN_DIFF()));
        }
    }

    public function _empty()
    {
        throw new HttpResponseException(json(GeneralRet::PAGE_NOT_EXISTS()));
    }

    public function heart()
    {
        return json(GeneralRet::SUCCESS());
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function user()
    {
        $data = self::check_params(['unionid']);

        $unionid = strval($data['unionid']);
        $ret = MemberOper::search_unionid($unionid);
        if (null === $ret || !is_numeric($ret['code'])) {
            return json(GeneralRet::PEOPLE_NOT_FOUND());
        }
        trace("查询 {$ret['unique_name']} {$ret['code']}", MysqlLog::RPC);
        $msg = GeneralRet::SUCCESS();
        $msg['user'] = intval($ret['code']);
        $msg['fee'] = FeeOper::owe($ret['unique_name']);
        $msg['status'] = $ret['status'];
        $fame = FameOper::getFameForUnique($ret['unique_name']);
        if (null !== $fame) {
            $msg['grade'] = $fame['grade'];
        }
        return json($msg);
    }

    public function temp()
    {
        $data = self::check_params(['touser']);

        if (
            !isset($data['template_id'])
            || !in_array($data["template_id"], WxTemp::temp_ids)
        ) {
            return json(GeneralRet::TEMP_ID_WRONG());
        }

        $unionid = strval($data['touser']);
        $ret = MemberOper::search_unionid($unionid);
        $unique_name = $unionid;
        if (null !== $ret) {
            $data['touser'] = $ret['openid'];
            if (is_numeric($ret['code'])) {
                $unique_name = $ret['unique_name'];
            }
        } else {
            return json(GeneralRet::REQUIRE_SUBSCRIBE());
        }

        $raw = WxTemp::rpc($data, "RPC 模板 $unique_name " . json_encode($data));
        if (strpos($raw, 'subscribe') !== FALSE) {
            SubscribeOper::mayUnsubscribe($data['touser']);
        }
        if ($raw === 'ok') {
            return json(GeneralRet::SUCCESS());
        } else {
            $ret = GeneralRet::TEMP_SEND_ERR();
            $ret['raw'] = $raw;
            return json($raw);
        }
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function act()
    {
        $data = self::check_params(['act', 'bonus', 'unionid', 'operid']);

        $bonus = intval($data['bonus']);
        if ($bonus < 5 || $bonus > 30) {
            return json(GeneralRet::BONUS_ERR());
        }

        $act = strval($data['act']);
        if (strpos($act, date('Y')) !== 0) {
            return json(GeneralRet::ACTIVITY_ERR());
        }

        $unionid = strval($data['unionid']);
        $ret = MemberOper::search_unionid($unionid);
        if (!is_numeric($ret['code']) || !in_array(intval($ret['code']), MemberOper::getMember())) {
            return json(GeneralRet::USER_LOCKED());
        }
        if (FeeOper::owe($ret['unique_name'])) {
            return json(GeneralRet::USER_OWE_FEE());
        }

        $operid = strval($data['operid']);
        $operret = MemberOper::search_unionid($operid);
        if (!is_numeric($operret['code']) || !in_array(intval($ret['code']), MemberOper::getMember())) {
            return json(GeneralRet::OPER_LOCKED());
        }
        if (FeeOper::owe($operret['unique_name'])) {
            return json(GeneralRet::OPER_OWE_FEE());
        }

        if (null === $ret || null === $operret) {
            return json(GeneralRet::PEOPLE_NOT_FOUND());
        }

        trace("活动 {$operret['unique_name']} -> {$ret['unique_name']}, $act, $bonus", MysqlLog::RPC);
        ActivityOper::signAct(
            $operret['unique_name'],
            $operret['openid'],
            $act,
            BonusOper::getVolBonus(),
            $operret['unique_name'],
            true
        );
        return ActivityOper::signAct(
            $ret['unique_name'],
            $ret['openid'],
            $act,
            $bonus,
            $operret['unique_name']
        );
    }

    public function refund()
    {
        $data = self::check_params(['transaction_id', 'out_refund_no', 'total_fee', 'refund_fee']);
        $input = new WxPayRefund();
        $input->SetTransaction_id($data['transaction_id']);
        $input->SetOut_refund_no($data['out_refund_no']);
        $input->SetTotal_fee($data['total_fee']);
        $input->SetRefund_fee($data['refund_fee']);

        $config = new HanbjPayConfig();
        $input->SetOp_user_id($config->GetMerchantId());
        try {
            $ret = WxPayApi::refund($config, $input);
            trace('退款 INFO ' . json_encode($data) . json_encode($ret), MysqlLog::RPC);
            $gen_ret = GeneralRet::SUCCESS();
            $gen_ret['data'] = $ret;
            return json($gen_ret);
        } catch (Exception $e) {
            trace('退款 ERROR ' . json_encode($data) . $e, MysqlLog::RPC);
            $gen_ret = GeneralRet::UNKNOWN();
            $gen_ret['status'] = $e;
            throw new HttpResponseException(json($gen_ret));
        }
    }

    public function payout()
    {
        $data = self::check_params(['payId', 'unionid', 'nickName', 'realName', 'orgName', 'activeName', 'payNum']);

        $openid = MemberOper::search_unionid(strval($data['unionid']));
        if (null === $openid) {
            return json(GeneralRet::PEOPLE_NOT_FOUND());
        }
        if (intval($openid['status']) !== SubscribeOper::Subscribe) {
            return GeneralRet::REQUIRE_SUBSCRIBE();
        }
        if (null === $openid['unique_name']) {
            $openid['unique_name'] = "";
        }

        $realname = strval($data['realName']);
        $real_desc = $realname;
        if ($realname === "NO_USE") {
            $real_desc = "";
        }

        $payId = strval($data['payId']);
        if (!preg_match("/^[A-Za-z0-9]+$/u", $payId)) {
            return GeneralRet::PAY_ID_INVALID();
        }
        $nick = strval($data['nickName']);
        $org = strval($data['orgName']);
        $act = strval($data['activeName']);
        $fee = intval($data['payNum']);
        if ($act === '实名认证') {
            $desc = "付款：【 $org 】组织的【 " . strval($openid['unique_name']) . " $nick $real_desc 】实名认证";
            $gen_ret = PayoutOper::recordNameVerifyPayout($openid['openid'], $payId, $realname, $fee, $desc, $nick, $org, $act);
        } else {
            $fee_desc = sprintf("%d.%02d", intval($fee / 100), intval($fee % 100));
            $desc = "付款：应【 $act 】的需求，向【 $org 】组织的【 " . strval($openid['unique_name']) . " $nick $real_desc 】付款人民币【 $fee_desc 】元";
            $gen_ret = PayoutOper::recordNewPayout($openid['openid'], $payId, $realname, $fee, $desc, $nick, $org, $act);
        }
        return json($gen_ret);
    }

    public function get_fame_unionid()
    {
        $data = self::check_params(['label', 'level']);
        $data = FameOper::getUnionId(strval($data['label']), strval($data['grade']), strval($data['type']));
        $ret = GeneralRet::SUCCESS();
        $ret['data'] = $data;
        $ret['len'] = count($data);
        return json($ret);
    }

    private function check_params($items)
    {
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        foreach ($items as $idx) {
            if (!isset($data[$idx])) {
                $gen_ret = GeneralRet::PARAMS_MISSING();
                $gen_ret['status'] = "缺失参数 $idx";
                throw new HttpResponseException(json($gen_ret));
            }
        }
        return $data;
    }
}
