<?php

namespace app\hanbj\controller;

use app\hanbj\UserOper;
use think\Controller;
use think\exception\HttpResponseException;

class Error extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if (UserOper::VERSION !== session('login')) {
            $res = redirect('/hanbj/index/bulletin');
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/error_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, '页面不存在', [$action]);
    }
}