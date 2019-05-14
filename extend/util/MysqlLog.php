<?php

namespace util;

use think\Db;

class MysqlLog
{
    const DEBUG = 'debug';
    const LOG = 'log';
    const ERROR = 'error';
    const INFO = 'info';
    const RPC = 'rpc';

    /**
     * 日志级别
     * @access public static
     * @param str $level 级别
     * @return array
     */
    public static function get_level($level = '')
    {
        switch ($level) {
            case self::RPC:
                return [self::RPC];
            case self::ERROR:
                return [self::ERROR];
            case self::INFO:
                return [self::ERROR, self::INFO];
            case self::LOG:
                return [self::ERROR, self::INFO, self::LOG];
            case self::DEBUG:
                return [self::ERROR, self::INFO, self::LOG, self::DEBUG];
            default:
                return [self::RPC, self::ERROR, self::INFO, self::LOG, self::DEBUG];
        }
    }

    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log = [])
    {
        $url = request()->url();
        $pos = strpos($url, '?');
        if ($pos) {
            $url = [
                substr($url, 0, $pos),
                substr($url, $pos + 1)
            ];
        } else {
            $url = [$url, ''];
        }
        $base = [
            'ip' => request()->ip(),
            'time' => date("Y-m-d H:i:s"),
            'method' => request()->method(),
            'url' => $url[0],
            'query' => $url[1]
        ];

        $logArr = [];
        foreach ($log as $type => $msgArr) {
            foreach ($msgArr as $msg) {
                $tmp = $base;
                $tmp['type'] = $type;
                $tmp['msg'] = is_string($msg) ? $msg : json_encode($msg);
                $logArr[] = $tmp;
            }
        }
        Db::table('logs')->insertAll($logArr);
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