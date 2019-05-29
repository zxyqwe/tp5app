<?php

namespace util\stat;

use DateInterval;
use DateTimeImmutable;
use think\Db;
use util\StatOper;

class LogStat extends BaseStat
{
    private $today;
    private $first_day;
    private $time_interval;

    function __construct()
    {
        $this->today = new DateTimeImmutable();
        $this->first_day = DateTimeImmutable::createFromFormat(StatOper::TIME_FORMAT, "2019-01-29");
        $this->time_interval = new DateInterval("P1D");
    }

    public function generateOneDay()
    {
        $current_new_day = StatOper::getQuery(StatOper::LOG_NUM)
            ->order('time desc')
            ->field('time as t')
            ->find();
        if (null === $current_new_day) {
            $current_new_day = $this->first_day;
        } else {
            $current_new_day = DateTimeImmutable::createFromFormat(StatOper::TIME_FORMAT, $current_new_day['t']);
            $current_new_day = $current_new_day->add($this->time_interval);
        }
        if ($current_new_day >= $this->today) {
            return false;
        }

        $fetch_date = $current_new_day->format(StatOper::TIME_FORMAT);
        $ret = Db::table('logs')
            ->where([
                'time' => ['like', $fetch_date . '%']
            ])
            ->group('type')
            ->field([
                'count(id) as num',
                'type'
            ])
            ->select();
        return [$fetch_date, json_encode($ret)];
    }

    public function OutputAll()
    {
        // TODO: Implement OutputAll() method.
    }
}