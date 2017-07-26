<?php

namespace app\hanbj\controller;

use think\Db;

class Wx
{
    public function json_activity()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = 1;
        $offset = max(0, $offset);
        $uname = session('unique_name');
        $uname = '坤乙未';
        $map['unique_name'] = $uname;
        $card = Db::table('activity')
            ->where($map)
            ->limit($offset, $size)
            ->order('act_time', 'desc')
            ->field([
                'oper',
                'name',
                'act_time'
            ])
            ->select();
        return json($card);
    }

}