<?php

namespace hanbj;


use hanbj\weixin\HanbjPayConfig;
use think\Db;
use think\exception\HttpResponseException;
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
    const MAX_FEE = 500000;

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
        $order = [
            'openid' => $to,
            'actname' => $act
        ];
        $ret = Db::table('payout')
            ->where($order)
            ->find();
        if (null !== $ret) {
            trace("重复实名认证 $to $act", MysqlLog::ERROR);
            return false;
        }

        Db::startTrans();
        try {
            $ret = self::recordNewPayout($to, $tradeid, $realname, $fee, $desc, $nick, $org, $act);
            if (!ret) {
                return false;
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
                ->data(['status' => self::AUTH])
                ->update();
            if ($ret === 1) {
                Db::commit();
            } else {
                throw new HttpResponseException(json(['msg' => '穿透二次审核失败'], 400));
            }
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function recordNewPayout($to, $tradeid, $realname, $fee, $desc, $nick, $org, $act)
    {
        $fee = intval($fee);
        if ($fee > self::MAX_FEE || $fee < self::MIN_FEE) {
            return false;
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
            return true;
        }
        $order['status'] = self::WAIT;
        $order['gene_time'] = date("Y-m-d H:i:s");
        try {
            $ret = Db::table('payout')
                ->data($order)
                ->insert();
            return $ret === 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("recordNewPayout $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
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
                'payment_time' => ''
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
                $item['id'],
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
                'payment_time' => ''
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
                'payment_time' => ''
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
            $ret = Db::table('payout')
                ->where([
                    'tradeid' => $ret['tradeid'],
                    'status' => self::AUTH,
                    'payment_no' => ['exp', Db::raw('is null')],
                    'payment_time' => ''
                ])
                ->data([
                    'payment_no' => $wx_ret['payment_no'],
                    'payment_time' => $wx_ret['payment_time'],
                    'status' => self::DONE
                ])
                ->update();
            if ($ret != 1) {
                trace("setPayoutDone {$ret['tradeid']}, {$wx_ret['payment_no']}, {$wx_ret['payment_time']}", MysqlLog::ERROR);
            }
            trace('Pay Out ' . json_encode($input->GetValues()) . ' ' . json_encode($wx_ret), MysqlLog::LOG);
        } else {
            trace('Pay Out ' . json_encode($input->GetValues()) . ' ' . json_encode($wx_ret), MysqlLog::ERROR);
        }
    }

    public static function notify_original() //1 done, 0 fail
    {
        $payout = Db::table("payout")
            ->where([
                'status' => ['in', [self::DONE, self::FAIL]]
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
