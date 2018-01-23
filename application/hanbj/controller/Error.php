<?php

namespace app\hanbj\controller;

use app\hanbj\model\UserOper;
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
            $res = redirect('https://app.zxyqwe.com/hanbj/pub/bulletin');
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
