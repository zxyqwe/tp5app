<?php

namespace hanbj;

use think\Db;

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
}