<?php

namespace app\hanbj\controller;

use hanbj\UserOper;
use hanbj\MemberOper;
use hanbj\HBConfig;
use hanbj\weixin\WxHanbj;
use think\Controller;
use think\exception\HttpResponseException;

class Index extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'index,old,cron']
    ];

    protected function valid_id()
    {
        UserOper::valid_pc();
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/index_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, '页面不存在', [$action]);
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

    public function debug()
    {
        $uniq = session('unique_name');
        if ($uniq !== HBConfig::CODER) {
            return json(['msg' => $uniq]);
        }

        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
//      $ret =   MemberOper::create_unique_unused();
        $ret = WxHanbj::addUnionID($access);

        return json(['msg' => $ret]);
    }

    public function cron()
    {
        define('TAG_TIMEOUT_EXCEPTION', true);
        $name = 'indexHanbjCron';
        if (cache("?$name"))
            return;
        cache($name, $name, 290);
        MemberOper::daily();
    }
}
