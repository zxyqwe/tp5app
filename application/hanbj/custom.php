<?php

namespace app\hanbj;

use think\Db;

class FeeOper
{
    public static function cache_fee($uname)
    {
        if (cache('?fee' . $uname)) {
            return cache('fee' . $uname);
        }
        $map['unique_name'] = $uname;
        $res = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->field([
                'count(oper) as s',
                'sum(f.code) as n'
            ])
            ->find();
        $year = Db::table('member')
            ->where($map)
            ->value('year_time');
        $fee = intval($year) + intval($res['s']) - 2 * intval($res['n']) - 1;
        cache('fee' . $uname, $fee, 600);
        return $fee;
    }
}