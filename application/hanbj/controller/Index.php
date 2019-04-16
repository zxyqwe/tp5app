<?php

namespace app\hanbj\controller;

use hanbj\UserOper;
use hanbj\MemberOper;
use think\Controller;
use think\exception\HttpResponseException;
use util\BackupOper;

class Index extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/index_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function index()
    {
        if (UserOper::VERSION === session('login')) {
            return redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        return view('login');
    }

    public function old()//需要这个，不然route就会屏蔽入口
    {
        return redirect('https://app.zxyqwe.com/hanbj/pub/bulletin');
    }

    public function logout()
    {
        session(null);
        return redirect('https://app.zxyqwe.com/hanbj/index/home');
    }

    public function cron()
    {
        local_cron();
        define('TAG_TIMEOUT_EXCEPTION', true);
        $name = 'indexHanbjCron';
        if (cache("?$name"))
            return;
        cache($name, $name, 60 - 10);
        MemberOper::daily();
        BackupOper::run();
    }
}
