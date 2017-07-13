<?php

namespace app\hanbj\controller;

use think\captcha;


class Index
{
    public function index()
    {
        //$access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        //$access = substr($access, 0, 5);
        if ('succ' === session('login')) {
            redirect('/hanbj/index/home');
        }
        $nonstr = getNonceStr();
        session('nonstr', $nonstr);
        return view('login', ['nonstr' => $nonstr]);
    }

    public function json_login()
    {
        $capt = input('post.capt');
        if (!captcha_check($capt)) {
            return json(['msg' => '验证码错误'], 400);
        }
        $mm = input('post.mm');
        $user = input('post.user');
        $nonstr = session('nonstr');
        $tmp = '';
        if ($mm !== $tmp) {
            return json(['msg' => '密码错误'], 400);
        }
        session('login', 'succ');
        return json(['msg' => ' 登录成功'], 200);
    }

    public function home()
    {
        
    }
}
