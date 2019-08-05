<?php

namespace hanbj;

use think\Db;
use util\MysqlLog;
use hanbj\weixin\WxTemp;

class TodoOper
{
    const PAT_OUT = 1;

    const UNDO = 0;
    const DONE = 1;
    const FAIL_FOREVER = 2;

    /**
     * @param int $type
     * @param array $content
     * @param string $unique_name
     * @return bool
     */
    public static function RecvTodoFromOtherOper($type, $key, $content, $unique_name)
    {
        try {
            return Db::table('todo')
                    ->data([
                        'type' => $type,
                        'key' => $key,
                        'content' => $content,
                        'unique_name' => $unique_name,
                        'time' => date("Y-m-d H:i:s"),
                        'status' => self::UNDO
                    ])
                    ->insert() === 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("RecvTodoFromOtherOper $e $type, $key, $content, $unique_name", MysqlLog::ERROR);
            return false;
        }
    }

    public static function noticeAny()
    {
        $ret = Db::table('todo')
            ->where([
                'status' => self::UNDO
            ])
            ->field([
                'unique_name'
            ])
            ->select();
        if (null === $ret) {
            return;
        }
        $notice = [];
        foreach ($ret as $item) {
            if (isset($notice[$item['unique_name']])) {
                $notice[$item['unique_name']] += 1;
            } else {
                $notice[$item['unique_name']] = 1;
            }
        }

        $unique_name = array_keys($notice);
        $ret = MemberOper::get_tieba($unique_name);
        $openid = [];
        foreach ($ret as $item) {
            $openid[$item['u']] = $item['o'];
        }

        foreach ($notice as $k => $v) {
            $cache_key = "TodonoticeAny$k";
            if (cache("?$cache_key")) {
                continue;
            }
            if (!isset($openid[$k])) {
                trace("Todo noticeAny $k no openid", MysqlLog::ERROR);
                continue;
            }
            cache($cache_key, $cache_key, 86400);
            WxTemp::notifyTodo($openid[$k], $k, $v);
        }
    }

    public static function showTodo()
    {
        $unique_name = session("unique_name");
        $map['status'] = self::UNDO;
        if (HBConfig::CODER !== $unique_name) {
            $map['unique_name'] = $unique_name;
        }
        return Db::table('todo')
            ->where($map)
            ->order('time desc')
            ->field([
                'key',
                'type',
                'content'
            ])
            ->select();
    }
}

/*
CREATE TABLE `todo` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL,
  `key` int(11) NOT NULL,
  `unique_name` varchar(45) NOT NULL,
  `content` varchar(2048) NOT NULL,
  `time` varchar(45) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
  UNIQUE KEY `typeid` (`type`, `key`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/
