<?php

namespace util;

use think\Db;

class MysqlLog
{
    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log = [])
    {
        $base = [
            'ip' => request()->ip(),
            'time' => date("Y-m-d H:i:s"),
            'method' => request()->method(),
            'url' => request()->baseUrl(),
            'query' => request()->query()
        ];

        $logArr = [];
        foreach ($log as $type => $msgArr) {
            foreach ($msgArr as $msg) {
                $tmp = $base;
                $tmp['type'] = $type;
                $tmp['msg'] = $msg;
                $logArr[] = $tmp;
            }
        }
        Db::table("logs")->insertAll($logArr);
        return true;
    }
}

/**
 * CREATE TABLE `logs` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `ip` varchar(45) NOT NULL,
 * `time` varchar(45) NOT NULL,
 * `method` varchar(45) NOT NULL,
 * `url` varchar(1024) NOT NULL,
 * `query` varchar(1024) NOT NULL,
 * `type` varchar(45) NOT NULL,
 * `msg` varchar(4096) NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */