<?php

namespace hanbj;

use think\Db;
use hanbj\vote\WxOrg;

class FameOper
{
    const chairman = 0;//会长
    const vice = 1;//副会长
    const manager = 2;//部长
    const deputy = 3;//副部长
    const member = 4;//干事
    const assistant = 5;//助理

    public static function getUp()
    {
        return self::get([self::chairman, self::vice, self::manager]);
    }

    public static function getTop()
    {
        return self::get([self::chairman, self::vice]);
    }

    public static function getDeputy()
    {
        return self::get([self::deputy]);
    }

    private static function get($group)
    {
        $map['year'] = WxOrg::year;
        $map['grade'] = ['in', $group];
        $ret = Db::table('fame')
            ->where($map)
            ->field('unique_name as u')
            ->cache(600)
            ->select();
        $data = [];
        foreach ($ret as $i) {
            $data[] = $i['u'];
        }
        return $data;
    }

    public static function clear($uname)
    {
        $map['unique_name'] = $uname;
        $data['unique_name'] = $uname . date("Y-m-d H:i:s");
        $ret = Db::table('fame')
            ->where($map)
            ->update($data);
        if (intval($ret) !== 0) {
            trace("Fame Clear $uname $ret");
        }
    }
}