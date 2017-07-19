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
        if (null === $res) {
            return view('reg');
        }
        return view('home', ['user' => $res]);
    }

    public function event()
    {
        include_once APP_PATH . 'hanbj/wx.php';
        //$access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        //$access = substr($access, 0, 5);
    }
}
