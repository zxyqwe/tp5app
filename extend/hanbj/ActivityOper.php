<?php

namespace hanbj;

use hanbj\weixin\WxTemp;
use think\Db;
use think\exception\HttpResponseException;

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
            trace("Act Clear $uname $ret");
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
            trace("登记活动 {$data['oper']} -> {$user}, $act_name, $bonus");
            WxTemp::regAct($openid, $user, $act_name);
            return json(['msg' => 'ok']);
        } catch (\Exception $e) {
            $e = $e->getMessage();
            if (false != strpos('' . $e, 'Duplicate')) {
                return json(['msg' => '重复登记活动'], 400);
            }
            trace("signAct $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    public static function revokeTest()
    {
        return Db::table('activity')
            ->where(['name' => ['like', '%测试%']])
            ->data(['bonus' => 0, 'up' => 1])
            ->update();
    }
}