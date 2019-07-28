<?php

namespace hanbj;


use think\Db;
use think\exception\HttpResponseException;
use util\MysqlLog;

class PayoutOper
{
    const URL = "https://active.qunliaoweishi.com/manage/api/rpc/v1/external_pay_callback.php";

    const WAIT = 0;
    const DONE = 1;

    const MIN_FEE = 30;
    const MAX_FEE = 500000;

    const AUTHOR = "乾乙丑";

    public static function recordNewPayout($to, $tradeid, $realname, $fee, $desc)
    {
        $fee = intval($fee);
        $fee = max($fee, self::MIN_FEE);
        $fee = min($fee, self::MAX_FEE);
        try {
            $ret = Db::table('payout')
                ->data([
                    'openid' => $to,
                    'tradeid' => $tradeid,
                    'realname' => $realname,
                    'fee' => $fee,
                    'desc' => $desc,
                    'gene_time' => date("Y-m-d H:i:s"),
                    'payment_no' => '',
                    'payment_time' => '',
                    'status' => self::WAIT
                ])
                ->insert();
            return $ret;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Unused2Temp $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    public static function generateAnyTodo()
    {

    }

    public static function handleOneTodo()
    {

    }

    public static function setPayoutDone($tradeid, $tran_no, $tran_time)
    {
        $ret = Db::table('payout')
            ->where([
                'tradeid' => $tradeid,
                'status' => self::WAIT,
                'payment_no' => '',
                'payment_time' => ''
            ])
            ->data([
                'payment_no' => $tran_no,
                'payment_time' => $tran_time,
                'status' => self::DONE
            ])
            ->update();
        return $ret == 1;
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
  `payment_no` varchar(255) NOT NULL,
  `payment_time` varchar(255) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tradeid` (`tradeid`),
  UNIQUE KEY `payment_no` (`payment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/