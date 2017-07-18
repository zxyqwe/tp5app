<?php

namespace app\hanbj\controller;


use think\Db;

class Mobile
{
    public function index()
    {
        if (!WX_iter()) {
            return WX_redirect('/hanbj/mobile');
        }
        $openid = session('openid');
        $map['c.openid'] = $openid;
        $res = Db::table('card')
            ->alias('c')
            ->join('member m', 'm.mcode=c.mcode')
            ->where($map)
            ->cache(86400)
            ->field([
                ''
            ])
            ->find();
    }

    public function event()
    {
        include_once APP_PATH . 'hanbj/';
    }
}
