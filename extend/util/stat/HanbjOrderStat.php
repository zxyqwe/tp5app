<?php

namespace util\stat;

use DateInterval;
use DateTimeImmutable;
use hanbj\OrderOper;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use util\MysqlLog;
use util\StatOper;

class HanbjOrderStat extends BaseStat
{
    function __construct()
    {
        $this->today = new DateTimeImmutable();
        $this->first_day = DateTimeImmutable::createFromFormat(StatOper::TIME_FORMAT, "2017-08-09");
        $this->time_interval = new DateInterval("P1D");
    }

    /**
     * @return array|bool|false
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function generateOneDay()
    {
        $fetch_date = self::fetch_date(StatOper::HANBJ_ORDER_NUM);
        if ($fetch_date === false) {
            return false;
        }
        $fetch_date = $fetch_date->format(StatOper::TIME_FORMAT);
        trace("HanbjOrderStat::generateOneDay $fetch_date", MysqlLog::INFO);
        $ret = Db::table('order')
            ->where([
                'time' => ['like', $fetch_date . '%']
            ])
            ->group('type')
            ->field([
                'sum(fee) as fee',
                'type'
            ])
            ->select();
        $desc = "$fetch_date;";
        foreach ($ret as $item) {
            $desc .= "{$item['type']}类型{$item['fee']};";
        }
        return [$fetch_date, json_encode($ret), $desc];
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function OutputAll()
    {
        $all_catg = OrderOper::get_level();
        return self::output(StatOper::HANBJ_ORDER_NUM, $all_catg);
    }

    /**
     * @param $select_ret
     * @param $all_catg
     * @return array
     */
    protected function build_kv($select_ret, $all_catg)
    {
        $data = [];
        foreach ($select_ret as $item) {
            $data[$item['type']] = $item['fee'];
        }
        foreach ($all_catg as $item) {
            if (!isset($data[$item])) {
                $data[$item] = 0;
            }
        }
        return $data;
    }
}