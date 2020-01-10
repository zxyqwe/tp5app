<?php

namespace hanbj;


use hanbj\weixin\HanbjPayConfig;
use think\Db;
use think\exception\HttpResponseException;
use util\GeneralRet;
use util\MysqlLog;
use wxsdk\pay\WxPayApi;
use wxsdk\pay\WxPayTransfer;

class PayoutOper
{
    const URL = "https://active.qunliaoweishi.com/manage/api/rpc/v1/external_pay_callback.php";

    const WAIT = 0;
    const TODO = 1;
    const AUTH = 2;
    const DONE = 3;
    const FAIL = 4;
    const DONE_NOTICE = 5;
    const FAIL_NOTICE = 6;

    const MIN_FEE = 30;
    const MAX_FEE = 2500000;

    const AUTHOR = "乾乙丑";

    private static function Speak($type)
    {
        $type = intval($type);
        switch ($type) {
            case self::WAIT:
                return "WAIT";
            case self::TODO:
                return "TODO";
            case self::AUTH:
                return "AUTH";
            case self::DONE:
                return "DONE";
            case self::FAIL:
                return "FAIL";
            case self::DONE_NOTICE:
                return "DONE_NOTICE";
            case self::FAIL_NOTICE:
                return "FAIL_NOTICE";
            default:
                return "Unknown";
        }
    }

    public static function recordNameVerifyPayout($to, $tradeid, $realname, $fee, $desc, $nick, $org, $act)
    {
        if ($fee !== 30) {
            return GeneralRet::NAME_VARIFY_FEE();
        }
        if ($realname === "NO_USE") {
            return GeneralRet::NAME_VARIFY_NAME();
        }
        $order = [
            'openid' => $to,
            'actname' => $act,
            'status' => ['in', [self::DONE, self::DONE_NOTICE]]
        ];
        $ret = Db::table('payout')
            ->where($order)
            ->order('id desc')
            ->field(['status'])
            ->find();
        if (null !== $ret) {
            return GeneralRet::NAME_VARIFY_DONE_BEFORE();
        }
        try {
            $ret = self::recordNewPayout($to, $tradeid, $realname, $fee, $desc, $nick, $org, $act, self::AUTH);
            if ($ret['msg'] !== 'ok') {
                return $ret;
            }
            $order = [
                'openid' => $to,
                'tradeid' => $tradeid,
                'realname' => $realname,
                'fee' => $fee,
                'actname' => $act
            ];
            return self::payWX($order);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function recordNewPayout($to, $tradeid, $realname, $fee, $desc, $nick, $org, $act, $first_step = self::WAIT)
    {
        $fee = intval($fee);
        if ($fee > self::MAX_FEE || $fee < self::MIN_FEE) {
            return GeneralRet::PAY_FEE_INVALID();
        }
        $order = [
            'openid' => $to,
            'tradeid' => $tradeid,
            'realname' => $realname,
            'fee' => $fee,
            'desc' => $desc,
            'nickname' => $nick,
            'orgname' => $org,
            'actname' => $act
        ];
        $ret = Db::table('payout')
            ->where($order)
            ->find();
        if (null !== $ret) {
            return GeneralRet::DUPLICATE_PAY();
        }
        $order['status'] = $first_step;
        $order['gene_time'] = date("Y-m-d H:i:s");
        try {
            $ret = Db::table('payout')
                ->data($order)
                ->insert();
            if ($ret === 1) {
                trace("付款 INFO $to, $tradeid, $realname, $fee, $desc, $nick, $org, $act", MysqlLog::INFO);
                return GeneralRet::SUCCESS();
            } else {
                return GeneralRet::PAY_RECORD();
            }
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("recordNewPayout $e", MysqlLog::ERROR);
            $gen_ret = GeneralRet::UNKNOWN();
            $gen_ret['status'] = $e;
            throw new HttpResponseException(json($gen_ret, 400));
        }
    }

    public static function generateAnyTodo()
    {
        Db::startTrans();
        $ret = self::generateAnyTodo_inner();
        if ($ret) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    private static function generateAnyTodo_inner()
    {
        $ret = Db::table('payout')
            ->where([
                'status' => self::WAIT,
                'payment_no' => ['exp', Db::raw('is null')],
                'payment_time' => '',
                'actname' => ['neq', '实名认证']
            ])
            ->field([
                'id',
                'realname',
                'fee',
                'desc'
            ])
            ->select();
        if (null === $ret) {
            return false;
        }
        $ids = [];
        foreach ($ret as $item) {
            $insert_todo = TodoOper::RecvTodoFromOtherOper(
                TodoOper::PAT_OUT,
                intval($item['id']),
                json_encode($item),
                self::AUTHOR
            );
            if ($insert_todo) {
                $ids[] = $item['id'];
            }
        }
        if (count($ids) === 0) {
            return false;
        }
        $ret = Db::table('payout')
            ->where([
                'id' => ['in', $ids],
                'status' => self::WAIT,
                'payment_no' => ['exp', Db::raw('is null')],
                'payment_time' => ''
            ])
            ->data([
                'status' => self::TODO
            ])
            ->update();
        if ($ret != count($ids)) {
            trace("generateAnyTodo payout $ret " . count($ids), MysqlLog::ERROR);
        }
        return $ret === count($ids);
    }

    public static function cancelOneTodo($key)
    {
        self::handleOneTodo($key, self::FAIL);
    }

    public static function authOneTodo($key)
    {
        self::handleOneTodo($key, self::AUTH);
    }

    private static function handleOneTodo($key, $event)
    {
        $unique_name = session("unique_name");
        if ($unique_name !== self::AUTHOR && $unique_name !== HBConfig::CODER) {
            return;
        }
        $ret = Db::table('payout')
            ->where([
                'id' => $key,
                'status' => self::TODO,
                'payment_no' => ['exp', Db::raw('is null')],
                'payment_time' => '',
                'actname' => ['neq', '实名认证']
            ])
            ->data([
                'status' => $event
            ])
            ->update();
        if ($ret != 1) {
            trace("付款待办 ID $key Status " . self::Speak($event), MysqlLog::ERROR);
            Db::rollback();
            throw new HttpResponseException(json(['msg' => "handleOneTodo($key, $event)"]));
        } else {
            trace("付款待办 ID $key Status " . self::Speak($event), MysqlLog::INFO);
        }
    }

    public static function handleOneAuth()
    {
        $ret = Db::table('payout')
            ->where([
                'status' => self::AUTH,
                'payment_no' => ['exp', Db::raw('is null')],
                'payment_time' => '',
                'actname' => ['neq', '实名认证']
            ])
            ->field([
                'tradeid',
                'openid',
                'realname',
                'fee',
                'actname'
            ])
            ->find();
        if (null === $ret) {
            return;
        }
        self::payWX($ret);
    }

    private static function payWX($ret)
    {
        $input = new WxPayTransfer();
        $input->SetOut_trade_no($ret['tradeid']);
        $input->SetOpen_id($ret['openid']);
        if ($ret['realname'] === 'NO_USE') {
            $input->SetCheck_name('NO_CHECK');
            $input->SetUser_name('NO_USE');
        } else {
            $input->SetCheck_name('FORCE_CHECK');
            $input->SetUser_name($ret['realname']);
        }
        $input->SetTotal_fee(intval($ret['fee']));
        $input->SetDesc($ret['actname']);
        $wx_ret = WxPayApi::payOut(new HanbjPayConfig(), $input);
        if (
            array_key_exists("return_code", $wx_ret)
            && array_key_exists("result_code", $wx_ret)
            && $wx_ret["return_code"] === "SUCCESS"
            && $wx_ret["result_code"] === "SUCCESS"
        ) {
            trace('Pay Out ' . json_encode($input->GetValues()) . ' ' . json_encode($wx_ret), MysqlLog::LOG);
            $next_stage = [
                'payment_no' => $wx_ret['payment_no'],
                'payment_time' => $wx_ret['payment_time'],
                'status' => self::DONE
            ];
            $gen_ret = GeneralRet::SUCCESS();
        } else {
            trace('Pay Out ' . json_encode($input->GetValues()) . ' ' . json_encode($wx_ret), MysqlLog::ERROR);
            $next_stage = ['status' => self::FAIL];
            $gen_ret = GeneralRet::NAME_VARIFY_WX_FAIL();
            $gen_ret['wx'] = $wx_ret;
        }
        $update_ret = Db::table('payout')
            ->where([
                'tradeid' => $ret['tradeid'],
                'status' => self::AUTH,
                'payment_no' => ['exp', Db::raw('is null')],
                'payment_time' => ''
            ])
            ->data($next_stage)
            ->update();
        if ($update_ret != 1) {
            trace("setPayout Next {$ret['tradeid']}, " . json_encode($next_stage), MysqlLog::ERROR);
        }
        return $gen_ret;
    }

    public static function notify_original() //1 done, 0 fail
    {
        $payout = Db::table("payout")
            ->where([
                'status' => ['in', [self::DONE, self::FAIL]],
                'actname' => ['neq', '实名认证']
            ])
            ->field([
                'tradeid',
                'status'
            ])
            ->find();
        if (null === $payout) {
            return;
        }
        $payout['status'] = intval($payout['status']);
        switch ($payout['status']) {
            case self::DONE:
                $data['status'] = 1;
                $final_status = 5;
                break;
            case self::FAIL:
                $data['status'] = 0;
                $final_status = 6;
                break;
            default:
                trace("notify_original select " . json_encode($payout), MysqlLog::ERROR);
                return;
        }
        $data['payId'] = $payout['tradeid'];
        $raw = Curl_Post($data, self::URL, true, 60);
        $ret = json_decode($raw, true);
        $output_str = "payId {$data['payId']} status {$data['status']} " . self::Speak($payout['status']) . " -> " . self::Speak($final_status);
        if (!isset($ret['code']) || $ret['code'] != 0) {
            trace("notify payout $output_str $raw", MysqlLog::ERROR);
        } else {
            trace("notify payout $output_str", MysqlLog::LOG);
            $ret = Db::table("payout")
                ->where([
                    'tradeid' => $data['payId'],
                    'status' => $payout['status']
                ])
                ->data(['status' => $final_status])
                ->update();
            if (1 !== $ret) {
                trace("update after notify err $output_str", MysqlLog::ERROR);
            }
        }
    }
}

/*
{"return_code":"SUCCESS","return_msg":[],"result_code":"SUCCESS",
"partner_trade_no":"1564107538",
"payment_no":"1486108052201907264293805101",
"payment_time":"2019-07-26 10:18:58"
}
CREATE TABLE `payout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(255) NOT NULL,
  `tradeid` varchar(255) NOT NULL,
  `realname` varchar(255) DEFAULT "NO_USE",
  `fee` int(11) NOT NULL,
  `desc` varchar(1024) NOT NULL,
  `gene_time` varchar(255) NOT NULL,
  `payment_no` varchar(255) DEFAULT NULL,
  `payment_time` varchar(255) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `nickname` varchar(255) NOT NULL,
  `orgname` varchar(255) NOT NULL,
  `actname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tradeid` (`tradeid`),
  UNIQUE KEY `payment_no` (`payment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/
