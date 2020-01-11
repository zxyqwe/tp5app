<?php

namespace app\hanbj\controller;

use DateTimeImmutable;
use hanbj\FameOper;
use hanbj\UserOper;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\response\Json;
use util\MysqlLog;
use wxsdk\pay\WxPayApi;
use wxsdk\pay\WxPayException;
use wxsdk\pay\WxPayUnifiedOrder;
use wxsdk\pay\WxPayJsApiPay;
use hanbj\BonusOper;
use hanbj\FeeOper;
use hanbj\HBConfig;
use hanbj\MemberOper;
use hanbj\OrderOper;
use hanbj\vote\WxVote;
use hanbj\weixin\HanbjNotify;
use hanbj\weixin\HanbjPayConfig;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;

class Wxdaily extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'notify,old']
    ];

    protected function valid_id()
    {
        if (!UserOper::wx_login()) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return json([], 404);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function json_activity()
    {
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = 5;
        $offset = max(0, $offset);
        $uname = session('unique_name');
        $map['unique_name'] = $uname;
        $card = Db::table('activity')
            ->where($map)
            ->limit($offset, $size)
            ->order('act_time', 'desc')
            ->field([
                'oper',
                'name',
                'act_time',
                'bonus',
                'up',
                'type'
            ])
            ->select();
        return json(['list' => $card, 'size' => $size]);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function json_valid()
    {
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = 5;
        $offset = max(0, $offset);
        $uname = session('unique_name');
        $map['unique_name'] = $uname;
        $card = Db::table('nfee')
            ->where($map)
            ->limit($offset, $size)
            ->order('fee_time', 'desc')
            ->field([
                'oper',
                'code',
                'fee_time',
                'bonus',
                'up'
            ])
            ->select();
        $fee_year = FeeOper::cache_fee($uname);
        return json([
            'list' => $card,
            'size' => $size,
            'real_year' => $fee_year->format('Y'),
            'real_year_str' => $fee_year->format('Y-m-d H:i:s'),
            'fee_status' => new DateTimeImmutable() > $fee_year
        ]);
    }

    public function json_renew()
    {
        $uname = session('unique_name');
        $key = "json_renew_$uname";
        if (cache("?$key")) {
            return json(['msg' => '每天可以重新核算一次'], 400);
        }
        cache($key, $key, 86400);
        $bonus = BonusOper::renew($uname);
        return json(['msg' => $bonus]);
    }

    public function fee_year()
    {
        return json(OrderOper::FEE_YEAR);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws PDOException
     * @throws WxPayException
     */
    public function order()
    {
        if (!session('?card')) {
            return json(['msg' => '没有会员卡'], 400);
        }
        $opt = input('post.opt', 0, FILTER_VALIDATE_INT);
        $type = input('post.type', OrderOper::FEE, FILTER_VALIDATE_INT);
        $input = new WxPayUnifiedOrder();
        if ($type === OrderOper::FEE) {
            if ($opt < 0 || $opt >= count(OrderOper::FEE_YEAR)) {
                return json(['msg' => '年数错误'], 400);
            }
            $input = OrderOper::fee($input, $opt);
            if (false === $input) {
                return json(['msg' => '下单失败'], 400);
            }
        } else {
            return json(['msg' => '参数错误'], 400);
        }
        $config = new HanbjPayConfig();
        $order = WxPayApi::unifiedOrder($config, $input);
        $msg = json_encode($order) . json_encode($input->ToXml());
        if (!array_key_exists('prepay_id', $order)) {
            trace($msg, MysqlLog::ERROR);
            OrderOper::dropfee($input->GetOut_trade_no(), $opt);
            return json(['msg' => $msg], 400);
        }
        trace($msg, MysqlLog::LOG);
        $jsapi = new WxPayJsApiPay();
        $jsapi->SetAppid($order["appid"]);
        $jsapi->SetTimeStamp('' . time());
        $jsapi->SetNonceStr(WxPayApi::getNonceStr());
        $jsapi->SetPackage("prepay_id=" . $order['prepay_id']);
        $jsapi->SetPaySign($jsapi->MakeSign($config));
        $data = $jsapi->GetValues();
        $data['timestamp'] = $data['timeStamp'];
        return json($data);
    }

    public function notify()
    {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            return '';
        }
        $hand = new HanbjNotify();
        $hand->Handle(new HanbjPayConfig(), false);
        return '';
    }

    public function old()
    {
        return redirect('https://app.zxyqwe.com/hanbj/wxdaily/notify');
    }

    public function json_tempid()
    {
        $member_code = session('member_code');
        if (!is_numeric($member_code) || !in_array(intval($member_code), MemberOper::getMember())) {
            return json(['msg' => '用户锁住'], 400);
        }
        $uniq = session('unique_name');
        if (FeeOper::owe($uniq)) {
            return json(['msg' => '欠费'], 400);
        }
        $tempid = 0;
        if (cache("?json_tempid$uniq")) {
            $tempid = cache("json_tempid$uniq");
        } else {
            while ($tempid === 0) {
                $tempnum = rand(1000, 9999);
                if (!cache("?tempnum$tempnum")) {
                    cache("tempnum$tempnum", '', 1800);
                    $tempid = $tempnum;
                    cache("json_tempid$uniq", $tempid, 1700);
                }
            }
        }
        $data['time'] = date("Y-m-d");
        $data['time2'] = date("H:i:s");
        $data['uniq'] = $uniq;
        $data['nick'] = session('tieba_id');
        trace("临时身份 $tempid {$data['uniq']} {$data['nick']}", MysqlLog::LOG);
        cache("tempnum$tempid", json_encode($data), 1800);
        return json(['msg' => 'OK', 'temp' => $tempid]);
    }

    /**
     * @return Json
     * @throws Exception
     * @throws PDOException
     */
    public function change()
    {
        $action = input('get.action');
        if (!in_array($action, ['pref', 'web_name'])) {
            return json(['msg' => '操作未知' . $action], 400);
        }
        $map['openid'] = session('openid');
        $map['unique_name'] = session('unique_name');
        $map[$action] = input('post.old');
        $data[$action] = input('post.new');
        if ($map[$action] === $data[$action]) {
            return json(['msg' => '内容相同'], 400);
        }
        if (strlen($data[$action]) > 60) {
            return json(['msg' => '字数太多'], 400);
        }
        $res = Db::table('member')
            ->where($map)
            ->update($data);
        if ($res === 0) {
            return json(['msg' => '更新失败'], 400);
        }
        trace(str_replace("\n", '|', "{$map['unique_name']} {$action} {$map[$action]} -> {$data[$action]}"), MysqlLog::LOG);
        return json(['msg' => 'OK']);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function history()
    {
        $map['unique_name'] = session('unique_name');
        $map['grade'] = ['neq', FameOper::leave];
        $ret = Db::table('fame')
            ->where($map)
            ->order('year desc')
            ->field([
                'year',
                'grade',
                'label'
            ])
            ->select();
        return json(['hist' => $ret]);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getvote()
    {
        $ret = WxVote::initView();
        if (null === $ret) {
            return json(['msg' => '没有投票权'], 400);
        }
        return json(['msg' => $ret]);
    }

    public function vote()
    {
        $member_code = session('member_code');
        if (!is_numeric($member_code) || intval($member_code) !== MemberOper::NORMAL) {
            return json(['msg' => '只接受实名投票'], 400);
        }
        $uniq = session('unique_name');
        if (FeeOper::owe($uniq)) {
            return json(['msg' => '只接受非欠费投票'], 400);
        }
        if (WxVote::IsExpired()) {
            return json(['msg' => '非投票时间']);
        }
        $ans = input('post.ans');
        $ans = explode(',', $ans);//a1,a2,a3
        if (count(array_intersect($ans, WxVote::HISTORY[HBConfig::YEAR])) !== count($ans)) {
            return json(['msg' => '候选人错误' . input('post.ans')], 400);
        }
        return WxVote::addAns($uniq, $ans);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function prom()
    {
        $ret = Db::table('prom')
            ->where(['show' => 0])
            ->field([
                'name', 'img', 'desc', 'info', 'master'
            ])
            ->order('seq')
            ->select();
        return json($ret);
    }
}
