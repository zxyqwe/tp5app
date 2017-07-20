<?php

namespace app\hanbj\controller;


use think\Db;

class Mobile
{
    public function index()
    {
        if (!WX_iter(config('hanbj_api'), config('hanbj_secret'))) {
            return WX_redirect('/hanbj/mobile', config('hanbj_api'));
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
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        return substr($access, 0, 5);
    }
}
