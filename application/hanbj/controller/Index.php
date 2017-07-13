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

    public function json_login()
    {
        $capt = input('post.capt');
        if (!captcha_check($capt)) {
            return json(['msg' => '验证码错误'], 400);
        }
        $mm = input('post.mm');
        $nonstr = session('nonstr');
        $user = input('post.user');
        $tmp = Db::table('user')->where(['name' => $user])->value('mm');
        $tmp = strtolower($tmp) . $nonstr;
        $tmp = sha1($tmp);
        if ($mm !== $tmp) {
            return json(['msg' => '密码错误'], 400);
        }
        session('login', 'succ');
        session('name', $user);
        return json(['msg' => ' 登录成功'], 200);
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

    public function json_bulletin()
    {
        $tmp = cache('hanbj_json_bulletin');
        if ($tmp) {
            return json(json_decode($tmp, true));
        }
        $map['m.code'] = 0;
        $map['f.unoper'] = ['EXP', 'IS NULL'];
        $join = [
            ['member m', 'm.unique_name=f.unique_name']
        ];
        $tmp = Db::table('fee')
            ->alias('f')
            ->join($join)
            ->where($map)
            ->group('f.unique_name')
            ->field([
                'sum(1) as s',
                'f.unique_name as u',
                'm.year_time as t'
            ])->select();
        cache('hanbj_json_bulletin', json_encode($tmp), 30);
        return json($tmp);
    }

    public function all()
    {
    }

    public function log()
    {
    }

    public function fee()
    {
    }

    public function create()
    {
    }
}
