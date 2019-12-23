<?php

namespace app\hanbj\behavior;

use hanbj\HBConfig;
use think\exception\HttpResponseException;
use hanbj\UserOper;
use util\MysqlLog;

class AccessControll
{
    const except_controller = [
        'mobile',
        'pub',
        'rpc',
        'rpcv2',
        'wxclub',
        'wxdaily',
        'wxtest',
        'wxwork',
    ];
    const except = [
        'index' => [
            'index',
            'old',
            'cron',
        ],
        'system' => [
            'server'
        ]
    ];

    public function run(/* &$call */)
    {
        // $instance \think\Controller
        // $action string
        $controller = strtolower(request()->controller());
        $action = strtolower(request()->action());
        if (request()->ip() === config('local_mech')) {
            return;
        }

        $uniq = session('unique_name');
        session('watermark', "汉服北京 $uniq " . date("Y-m-d H:i:s"));
        if ($uniq === HBConfig::CODER) {
            return;
        }
        if (UserOper::grantAllRight($uniq)) {
            trace("超级权限 $uniq $controller $action", MysqlLog::LOG);
            return;
        }
        if (in_array($controller, self::except_controller)) {
            return;
        }
        if (array_key_exists($controller, self::except)
            && in_array($action, self::except[$controller])
        ) {
            trace("非限制方法 $uniq $controller $action", MysqlLog::LOG);
            return;
        }

        UserOper::valid_pc(request()->isAjax());
        if ($this->canView($controller, $action)) {
            return;
        }
        if (strlen($uniq) > 0) {
            trace("禁止 $uniq $controller $action", MysqlLog::INFO);
        }
        if (request()->isAjax()) {
            $res = json(['msg' => "没有权限 禁止 $uniq $controller $action"], 400);
        } else {
            $res = redirect('https://app.zxyqwe.com/hanbj/index/index');
        }
        throw new HttpResponseException($res);
    }

    private function canView($controller, $action)
    {
        if (in_array($controller, ['fame', 'analysis'])) {
            return true;
        }
        if ($controller === 'index' && $action === 'home') {
            return true;
        }
        if ($controller === 'write' && $action === 'fee_search') {
            return true;
        }
        return false;
    }
}
