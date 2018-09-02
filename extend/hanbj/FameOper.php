<?php

namespace hanbj;

use think\Db;
use hanbj\vote\WxOrg;

class FameOper
{
    const chairman = 0;//会长
    const vice_chairman = 1;//副会长
    const manager = 2;//部长
    const vice_manager = 3;//副部长
    const member = 4;//干事
    const assistant = 5;//助理
    const commissioner = 6;//专员
    const secretary = 7;//秘书长
    const vice_secretary = 8;//副秘书长
    const order = [
        self::chairman,
        self::vice_chairman,
        self::secretary,
        self::manager,
        self::vice_secretary,
        self::vice_manager,
        self::commissioner,
        self::assistant,
        self::member
    ];

    public static function getUp()//会长层、部长
    {
        return self::get([
            self::chairman,
            self::vice_chairman,
            self::secretary,
            self::manager
        ]);
    }

    public static function getTop()//会长层
    {
        return self::get([
            self::chairman,
            self::vice_chairman
        ]);
    }

    public static function get($group)
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

    public static function cmp($a, $b)
    {
        $order = array_flip(self::order);
        //year desc,grade asc,label asc
        if ($a['year'] !== $b['year']) {
            return $a['year'] < $b['year'] ? 1 : -1;
        }
        if ($a['grade'] !== $b['grade']) {
            return $order[$a['grade']] < $order[$b['grade']] ? -1 : 1;
        }
        if ($a['label'] !== $b['label']) {
            return $a['label'] < $b['label'] ? -1 : 1;
        }
        return 0;
    }

    public static function sort($ret)
    {
        usort($ret, ['FameOper', 'cmp']);
        return $ret;
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