<?php

namespace app\books\behavior;

use books\BConfig;
use think\exception\HttpResponseException;
use util\MysqlLog;

class AccessControll
{
    public function run(/* &$call */)
    {
        // $instance \think\Controller
        // $action string
        $controller = strtolower(request()->controller());
        $action = strtolower(request()->action());
        if (request()->ip() === config('local_mech')) {
            return;
        }
        if ($controller === 'index') {
            return;
        }

        $uniq = session('unique_name');
        if (in_array($uniq, BConfig::valid_user)) {
            return;
        }
        if (strlen($uniq) > 0) {
            trace("禁止 $uniq $controller $action", MysqlLog::INFO);
        }
        if (request()->isAjax()) {
            $res = json(['msg' => '没有权限'], 400);
        } else {
            $res = redirect('https://app.zxyqwe.com/books');
        }
        throw new HttpResponseException($res);
    }
}