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

    public static function signAct($user, $openid, $act_name, $bonus)
    {
        if (strlen($user) <= 1) {
            return json(['msg' => '用户错误'], 400);
        }
        $data['unique_name'] = $user;
        $data['oper'] = session('unique_name');
        $data['act_time'] = date("Y-m-d H:i:s");
        $data['name'] = $act_name;
        $data['bonus'] = $bonus;
        try {
            Db::table('activity')
                ->data($data)
                ->insert();
            WxTemp::regAct($openid, $user, BonusOper::getActName());
            return json(['msg' => 'ok']);
        } catch (\Exception $e) {
            if (false != strpos('' . $e, 'constraint')) {
                return json(['msg' => 'ok']);
            }
            $e = $e->getMessage();
            trace("signAct $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }
}