<?php

namespace hanbj;

use think\Db;
use think\exception\HttpResponseException;
use util\MysqlLog;
use hanbj\weixin\WxTemp;

class TodoOper
{
    const PAT_OUT = 1;

    const UNDO = 0;
    const DONE = 1;
    const FAIL_FOREVER = 2;

    const VALID_TYPE = [self::PAT_OUT];
    const VALID_RESULT = [self::DONE, self::FAIL_FOREVER];

    private static function Speak($type)
    {
        $type = intval($type);
        switch ($type) {
            case self::UNDO:
                return "UNDO";
            case self::DONE:
                return "DONE";
            case self::FAIL_FOREVER:
                return "FAIL_FOREVER";
            default:
                return "Unknown";
        }
    }

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
            ->order('time desc, id desc')
            ->field([
                'key',
                'type',
                'content'
            ])
            ->select();
    }

    public static function handleTodo($type, $key, $result)
    {
        $result = intval($result);
        $type = intval($type);
        $key = intval($key);
        if (!in_array($type, self::VALID_TYPE) ||
            !in_array($result, self::VALID_RESULT)
        ) {
            throw new HttpResponseException(json(['msg' => "handleTodo($type, $key, $result) invalid"]));
        }
        $unique_name = session("unique_name");
        $map['status'] = self::UNDO;
        $map['type'] = $type;
        $map['key'] = $key;
        if (HBConfig::CODER !== $unique_name) {
            $map['unique_name'] = $unique_name;
        }

        Db::startTrans();
        $ret = Db::table("todo")
            ->where($map)
            ->data(["status" => $result])
            ->update();
        if ($ret !== 1) {
            Db::rollback();
            throw new HttpResponseException(json(['msg' => "handleTodo($type, $key, $result) no update"]));
        }
        self::handleDetail($type, $key, $result);
        Db::commit();
        trace("handleTodo(type $type, ID $key, ret $result) " . self::Speak($result), MysqlLog::INFO);
    }

    private static function handleDetail($type, $key, $result)
    {
        if ($type === self::PAT_OUT) {
            if ($result === self::DONE) {
                PayoutOper::authOneTodo($key);
            } else {
                PayoutOper::cancelOneTodo($key);
            }
        } else {
            Db::rollback();
            throw new HttpResponseException(json(['msg' => "handleDetail($type, $key, $result) type err"]));
        }
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
