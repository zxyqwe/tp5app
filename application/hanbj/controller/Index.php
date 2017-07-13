<?php

namespace app\hanbj\controller;

use think\captcha;


class Index
{
    public function index()
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $access = substr($access, 0, 5);
        return view('login', ['acc' => $access]);
    }

    public function json_login()
    {
        $capt = input('post.capt');
        if (!captcha_check($capt)) {

        }
    }
}
