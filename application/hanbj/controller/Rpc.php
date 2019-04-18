<?php

namespace app\hanbj\controller;

use Exception;
use hanbj\ActivityOper;
use hanbj\BonusOper;
use hanbj\FeeOper;
use hanbj\MemberOper;
use hanbj\weixin\HanbjPayConfig;
use hanbj\weixin\WxTemp;
use think\Controller;
use think\exception\HttpResponseException;
use util\MysqlLog;
use wxsdk\pay\WxPayApi;
use wxsdk\pay\WxPayRefund;

class Rpc extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            throw new HttpResponseException(json(['msg' => '空 POST body']));
        }
        $ts = intval(input('get.ts'));
        if (abs(time() - $ts) > 1800) {
            throw new HttpResponseException(json(['msg' => 'ts误差太大']));
        }
        $sign = $GLOBALS['HTTP_RAW_POST_DATA'] . config('hanbj_rpc_sk') . $ts;
        $sign = md5($sign);
        $post_sign = input('get.sign');
        if ($post_sign !== $sign) {
            throw new HttpResponseException(json(['msg' => "错误校验 $post_sign"]));
        }
    }

    public function _empty()
    {
        throw new HttpResponseException(json(['msg' => '页面不存在']));
    }

    public function user()
    {
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        if (!isset($data['unionid'])) {
            return json(['msg' => '无unionID']);
        }
        $unionid = strval($data['unionid']);
        $ret = MemberOper::search_unionid($unionid);
        if (null === $ret) {
            return json(['msg' => "查无此人"]);
        }
        trace("查询 {$ret['unique_name']} {$ret['code']}", MysqlLog::RPC);
        return json([
            'msg' => 'ok',
            'user' => intval($ret['code']),
            'fee' => FeeOper::owe($ret['unique_name'])
        ]);
    }

    public function temp()
    {
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        if (!isset($data['touser'])) {
            return json(['msg' => '无unionID']);
        }
        if (!isset($data['template_id'])
            || !in_array($data["template_id"], WxTemp::temp_ids)
        ) {
            return json(['msg' => 'template_id错误']);
        }

        $unionid = strval($data['touser']);
        $ret = MemberOper::search_unionid($unionid);
        if (null === $ret) {
            return json(['msg' => "查无此人"]);
        }
        if (!is_numeric($ret['code']) || !in_array(intval($ret['code']), MemberOper::getMember())) {
            return json(['msg' => "用户锁住"]);
        }
        if (FeeOper::owe($ret['unique_name'])) {
            return json(['msg' => "用户欠费"]);
        }

        $data['touser'] = $ret['openid'];
        $raw = WxTemp::rpc($data, "RPC 模板 {$ret['unique_name']} " . json_encode($data));
        return json(['msg' => $raw]);
    }

    public function act()
    {
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        if (!isset($data['act'])
            || !isset($data['bonus'])
            || !isset($data['unionid'])
            || !isset($data['operid'])
        ) {
            return json(['msg' => '缺失参数']);
        }

        $bonus = intval($data['bonus']);
        if ($bonus < 5 || $bonus > 30) {
            return json(['msg' => 'bonus err']);
        }

        $act = strval($data['act']);
        if (strpos($act, date('Y')) !== 0) {
            return json(['msg' => 'act err']);
        }

        $unionid = strval($data['unionid']);
        $ret = MemberOper::search_unionid($unionid);
        if (!is_numeric($ret['code']) || !in_array(intval($ret['code']), MemberOper::getMember())) {
            return json(['msg' => "用户锁住"]);
        }
        if (FeeOper::owe($ret['unique_name'])) {
            return json(['msg' => "用户欠费"]);
        }

        $operid = strval($data['operid']);
        $operret = MemberOper::search_unionid($operid);
        if (!is_numeric($operret['code']) || !in_array(intval($ret['code']), MemberOper::getMember())) {
            return json(['msg' => "操作者锁住"]);
        }
        if (FeeOper::owe($operret['unique_name'])) {
            return json(['msg' => "操作者欠费"]);
        }

        if (null === $ret || null === $operret) {
            return json(['msg' => "查无此人"]);
        }

        trace("活动 {$operret['unique_name']} -> {$ret['unique_name']}, $act, $bonus", MysqlLog::RPC);
        ActivityOper::signAct(
            $operret['unique_name'],
            $operret['openid'],
            $act . '志愿者',
            BonusOper::getVolBonus(),
            $operret['unique_name']
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
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        if (!isset($data['transaction_id'])
            || !isset($data['out_refund_no'])
            || !isset($data['total_fee'])
            || !isset($data['refund_fee'])
        ) {
            return json(['msg' => '缺失参数']);
        }
        $input = new WxPayRefund();
        $input->SetTransaction_id($data['transaction_id']);
        $input->SetOut_refund_no($data['out_refund_no']);
        $input->SetTotal_fee($data['total_fee']);
        $input->SetRefund_fee($data['refund_fee']);

        $config = new HanbjPayConfig();
        $input->SetOp_user_id($config->GetMerchantId());
        try {
            $ret = WxPayApi::refund($config, $input);
            trace('退款 INFO' . json_encode($data) . json_encode($ret), MysqlLog::RPC);
            return json(['msg' => 'ok', 'data' => $ret]);
        } catch (Exception $e) {
            trace('退款 ERROR' . json_encode($data) . $e, MysqlLog::RPC);
            throw new HttpResponseException(json(['msg' => "$e"]));
        }
    }
}