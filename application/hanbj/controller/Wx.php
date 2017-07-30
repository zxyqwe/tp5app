<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'hanbj/custom.php';
include_once APP_PATH . 'hanbj/WxConfig.php';
include_once APP_PATH . 'WxPay.php';
use think\Db;
use app\HanbjNotify;
use app\hanbj\FeeOper;

class Wx
{
    public function json_activity()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = 5;
        $offset = max(0, $offset);
        $uname = session('unique_name');
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
        return json(['list' => $card, 'size' => $size]);
    }

    public function json_valid()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = 5;
        $offset = max(0, $offset);
        $uname = session('unique_name');
        $map['unique_name'] = $uname;
        $card = Db::table('nfee')
            ->where($map)
            ->limit($offset, $size)
            ->order('fee_time', 'desc')
            ->field([
                'oper',
                'code',
                'fee_time'
            ])
            ->select();
        return json(['list' => $card, 'size' => $size, 'real_year' => FeeOper::cache_fee($uname)]);
    }

    public function notify()
    {
        $hand = new HanbjNotify();
        $hand->Handle(false);
    }
}