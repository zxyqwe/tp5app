<?php

namespace util;


use hanbj\weixin\WxTemp;
use think\Db;
use think\exception\HttpResponseException;
use util\stat\BaseStat;
use util\stat\HanbjOrderStat;
use util\stat\LogStat;

class StatOper
{
    const TIME_FORMAT = "Y-m-d";

    const LOG_NUM = 0;
    const HANBJ_ORDER_NUM = 1;

    /**
     * @param int $type
     * @return \think\Db\Query
     */
    public static function getQuery($type)
    {
        return Db::table('stat')
            ->where([
                'type' => intval($type)
            ]);
    }

    public static function generateOneDay($type)
    {
        $stat = self::getRealStatOper($type);
        $ret = $stat->generateOneDay();
        if (false === $ret) {
            return 0;
        }
        $fetch_date = $ret[0];
        $content = $ret[1];
        $desc = $ret[2];
        $update_num = Db::table('stat')
            ->cache("StatOper" . $type)
            ->data([
                'type' => $type,
                'content' => $content,
                'time' => $fetch_date
            ])
            ->insert();
        if ($update_num) {
            WxTemp::notifyStat($type, strval($desc));
        }
        return $update_num === 1;
    }

    public static function OutputAll($type)
    {
        $stat = self::getRealStatOper($type);
        return $stat->OutputAll();
    }

    /**
     * @param int $type
     * @return BaseStat
     */
    private static function getRealStatOper($type)
    {
        switch ($type) {
            case self::LOG_NUM:
                return new LogStat();
            case self::HANBJ_ORDER_NUM:
                return new HanbjOrderStat();
            default:
                if (request()->isAjax()) {
                    $res = json(['msg' => "stat type error $type"], 400);
                } else {
                    $res = redirect('https://app.zxyqwe.com/hanbj/index/index');
                }
                throw new HttpResponseException($res);
        }
    }
}

/*
CREATE TABLE `stat` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL,
  `content` varchar(2048) NOT NULL,
`time` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `time_type` (`time`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/