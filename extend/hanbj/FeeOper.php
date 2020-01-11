<?php

namespace hanbj;


use DateInterval;
use DateTimeImmutable;
use Exception;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use util\MysqlLog;

class FeeOper
{
    const ADD = 0;
    const CACHE_NAME = "cache_fee_v2";

    /**
     * @param $uname
     * @return DateTimeImmutable
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws Exception
     */
    public static function cache_fee($uname)
    {
        $cache_name = self::CACHE_NAME . $uname;
        if (cache("?$cache_name")) {
            return cache($cache_name);
        }
        $map['unique_name'] = $uname;

        $start_year = Db::table('member')
            ->where($map)
            ->field('start_time')
            ->find();
        if (null === $start_year) {
            return (new DateTimeImmutable())->sub(new DateInterval("P1D"));
        }
        $start_year = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $start_year);

        $fee_year = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->field([
                'sum(f.code) as n'
            ])
            ->find();
        if (null === $fee_year) {
            return (new DateTimeImmutable())->sub(new DateInterval("P1D"));
        }
        $fee_year = intval($fee_year['n']);
        $fee_interval = new DateInterval("P" . abs($fee_year) . "Y");
        if ($fee_year > 0) {
            $start_year = $start_year->add($fee_interval);
        } elseif ($fee_year < 0) {
            $start_year = $start_year->sub($fee_interval);
        }

        cache($cache_name, $start_year);
        return $start_year;
    }

    /**
     * @param $uname
     * @param int $ignore_years
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public static function owe($uname, $ignore_years = 0)
    {
        $fee_year = self::cache_fee($uname);
        $ignore_interval = new DateInterval("P" . abs($ignore_years) . "Y");
        if ($ignore_years > 0) {
            $fee_year = $fee_year->sub($ignore_interval);
        } elseif ($ignore_years < 0) {
            $fee_year = $fee_year->add($ignore_interval);
        }
        return $fee_year < new DateTimeImmutable();
    }

    /**
     * @param $uname
     * @throws \think\Exception
     * @throws PDOException
     */
    public static function clear($uname)
    {
        $map['unique_name'] = $uname;
        $data['unique_name'] = $uname . date("Y-m-d H:i:s");
        $ret = Db::table('nfee')
            ->where($map)
            ->update($data);
        if (intval($ret) !== 0) {
            trace("Fee Clear $uname $ret", MysqlLog::INFO);
        }
        self::uncache($uname);
    }

    public static function uncache($uname)
    {
        cache(self::CACHE_NAME . $uname, null);
    }

    /**
     * @param $uname
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function info($uname)
    {
        $fee_year = self::cache_fee($uname);
        $owe = self::owe($uname);
        return "$uname 到期 " . $fee_year->format("Y-m-d H:i:s") . " 欠费 $owe";
    }
}
