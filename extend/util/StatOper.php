<?php

namespace util;


use think\Db;
use think\exception\HttpResponseException;
use util\stat\BaseStat;
use util\stat\LogStat;

class StatOper
{
    const TIME_FORMAT = "Y-m-d";

    const LOG_NUM = 0;

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
        return $stat->generateOneDay();
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
    private function getRealStatOper($type)
    {
        switch ($type) {
            case self::LOG_NUM:
                return new LogStat();
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/