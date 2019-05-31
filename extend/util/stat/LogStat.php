<?php

namespace util\stat;

use DateInterval;
use DateTimeImmutable;
use think\Db;
use util\MysqlLog;
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
        trace("LogStat::generateOneDay $fetch_date", MysqlLog::LOG);
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
        $all_catg = MysqlLog::get_level();
        $template = [];
        foreach ($all_catg as $item) {
            $template[$item] = [];
        }
        $time_range = [];
        $content = StatOper::getQuery(StatOper::LOG_NUM)
            ->order('time asc')
            ->field(['time', 'content'])
            ->cache(3600)
            ->select();
        foreach ($content as $item) {
            $time_range[] = $item['time'];
            $data = json_decode($item['content'], true);
            $data = $this->build_kv($data);
            foreach ($all_catg as $k) {
                $template[$k][] = $data[$k];
            }
        }
        return [$time_range, $template];
    }

    private function build_kv($select_ret)
    {
        $all_catg = MysqlLog::get_level();
        $data = [];
        foreach ($select_ret as $item) {
            $data[$item['type']] = $item['num'];
        }
        foreach ($all_catg as $item) {
            if (!isset($data[$item])) {
                $data[$item] = 0;
            }
        }
        return $data;
    }
}