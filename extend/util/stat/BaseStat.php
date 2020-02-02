<?php

namespace util\stat;


use DateTimeImmutable;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use util\StatOper;

abstract class BaseStat
{
    protected $today;
    protected $first_day;
    protected $time_interval;

    /**
     * @return false|array(string, string)
     */
    abstract public function generateOneDay();

    abstract public function OutputAll();

    /**
     * @param $select_ret
     * @param $all_catg
     * @return array
     */
    abstract protected function build_kv($select_ret, $all_catg);

    /**
     * @param $stat_type
     * @param $all_catg
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    protected function output($stat_type, $all_catg)
    {
        $template = [];
        foreach ($all_catg as $item) {
            $template[$item] = [];
        }
        $time_range = [];
        $content = StatOper::getQuery($stat_type)
            ->order('time asc')
            ->field(['time', 'content'])
            ->cache("StatOper" . $stat_type, 3600)
            ->select();
        foreach ($content as $item) {
            $time_range[] = $item['time'];
            $data = json_decode($item['content'], true);
            $data = $this->build_kv($data, $all_catg);
            foreach ($all_catg as $k) {
                $template[$k][] = $data[$k];
            }
        }
        return [
            'time' => $time_range,
            'data' => $template
        ];
    }

    /**
     * @param $stat_type
     * @return bool|DateTimeImmutable
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function fetch_date($stat_type)
    {
        $current_new_day = StatOper::getQuery($stat_type)
            ->order('time desc')
            ->field('time as t')
            ->find();
        if (null === $current_new_day) {
            $current_new_day = $this->first_day;
        } else {
            $current_new_day = DateTimeImmutable::createFromFormat(StatOper::TIME_FORMAT, $current_new_day['t']);
            $current_new_day = $current_new_day->add($this->time_interval);
        }
        $current_new_day = $current_new_day->setTime(0, 0, 0, 0);
        if ($current_new_day >= $this->today) {
            return false;
        }
        return $current_new_day;
    }
}