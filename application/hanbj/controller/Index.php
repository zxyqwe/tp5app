<?php

namespace app\hanbj\controller;

use app\hanbj\MemberOper;
use app\hanbj\OrderOper;
use app\hanbj\UserOper;
use think\Controller;
use think\exception\HttpResponseException;

class Index extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'index,old']
    ];

    protected function valid_id()
    {
        if (UserOper::VERSION !== session('login')) {
            $res = redirect('https://app.zxyqwe.com/hanbj/pub/bulletin');
            throw new HttpResponseException($res);
        }
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
    }
}
