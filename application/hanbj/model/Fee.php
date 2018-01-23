<?php

namespace app\hanbj\model;


use think\Db;

class FeeOper
{
    const ADD = 0;

    public static function cache_fee($uname)
    {
        $cache_name = 'cache_fee' . $uname;
        if (cache('?' . $cache_name)) {
            return cache($cache_name);
        }
        $map['unique_name'] = $uname;
        $res = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->field([
                'sum(f.code) as n'
            ])
            ->find();
        if (null === $res) {
            return 0;
        }
        $year = Db::table('member')
            ->where($map)
            ->value('year_time');
        $fee = intval($year) + intval($res['n']) - 1;
        cache($cache_name, $fee);
        return $fee;
    }

    public static function owe($uname, $off = 0)
    {
        return self::cache_fee($uname) < intval(date('Y')) + $off;
    }

    public static function clear($uname)
    {
        $map['unique_name'] = $uname;
        $data['unique_name'] = $uname . date("Y-m-d H:i:s");
        $ret = Db::table('nfee')
            ->where($map)
            ->update($data);
        trace("Fee Clear $uname $ret");
        self::uncache($uname);
    }

    public static function uncache($uname)
    {
        cache('cache_fee' . $uname, null);
    }
}
