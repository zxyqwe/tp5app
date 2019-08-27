<?php

namespace hanbj;

use hanbj\weixin\WxTemp;
use think\Db;
use think\exception\HttpResponseException;
use util\MysqlLog;

class ActivityOper
{
    public static function clear($uname)
    {
        $map['unique_name'] = $uname;
        $data['unique_name'] = $uname . date("Y-m-d H:i:s");
        $ret = Db::table('activity')
            ->where($map)
            ->update($data);
        if (intval($ret) !== 0) {
            trace("Act Clear $uname $ret", MysqlLog::INFO);
        }
    }

    public static function signAct($user, $openid, $act_name, $bonus, $oper = null)
    {
        if (strlen($user) <= 1) {
            return json(['msg' => '用户错误'], 400);
        }
        $data['unique_name'] = $user;
        $data['oper'] = $oper === null ? session('unique_name') : $oper;
        $data['act_time'] = date("Y-m-d H:i:s");
        $data['name'] = $act_name;
        $data['bonus'] = $bonus;
        try {
            Db::table('activity')
                ->data($data)
                ->insert();
            trace("登记活动 {$data['oper']} -> {$user}, $act_name, $bonus", MysqlLog::INFO);
            WxTemp::regAct($openid, $user, $act_name);
            return json(['msg' => 'ok']);
        } catch (\Exception $e) {
            $e = $e->getMessage();
            if (false != strpos('' . $e, 'Duplicate')) {
                return json(['msg' => '重复登记活动'], 400);
            }
            trace("signAct $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    public static function revokeTest()
    {
        $cache_key = 'revokeTest';
        if (cache("?$cache_key")) {
            return;
        }
        cache($cache_key, $cache_key, 86400);
        $ret = Db::table('activity')
            ->where(['name' => ['like', '%测试%']])
            ->data(['bonus' => 0, 'up' => 1])
            ->update();
        trace("revokeTest $ret", MysqlLog::INFO);
        return;
    }
}

/*
CREATE TABLE `activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `unique_name` varchar(45) NOT NULL,
  `oper` varchar(45) NOT NULL,
  `act_time` varchar(45) NOT NULL,
  `type` int(11) NOT NULL,
  `up` tinyint(4) NOT NULL,
  `bonus` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `act_uniq` (`name`,`unique_name`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */