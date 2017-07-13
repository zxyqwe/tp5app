<?php

namespace app\hanbj\controller;

use think\captcha;
use think\Db;


class Index
{
    public function index()
    {
        //$access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        //$access = substr($access, 0, 5);
        if ('succ' === session('login')) {
            return redirect('/hanbj/index/home');
        }
        $nonstr = getNonceStr();
        session('nonstr', $nonstr);
        return view('login', ['nonstr' => $nonstr]);
    }

    public function home()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('home', ['name' => session('name')]);
    }

    public function old()
    {
        return redirect('/hanbj/index/bulletin');
    }

    public function bulletin()
    {
        return view('bulletin');
    }

    public function all()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
    }

    public function log()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
    }

    public function fee()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
    }

    public function create()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
    }
}
