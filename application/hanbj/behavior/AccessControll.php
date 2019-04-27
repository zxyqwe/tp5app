<?php

namespace app\hanbj\behavior;

use hanbj\HBConfig;
use think\exception\HttpResponseException;
use hanbj\UserOper;
use util\MysqlLog;

class AccessControll
{
    const limit_controller = [
        'analysis',
        'daily',
        'develop',
        'error',
        'fame',
        'index',
        'system',
        'write',
        //'wxwork',
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
    const ROLE_SYS = 0; // 啥都干，基本是会长层
    const ROLE_TOP = 1; // 可信人员，基本是部长层
    const ROLE_MID = 2; // 一般工作人员，基本是副部长
    const ROLE_LOW = 3; // 只能看的，一般干事
    const ROLE_NON = 4; // 离职人员

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
        if (UserOper::grantAllRight($uniq)) {
            if ($uniq !== HBConfig::CODER) {
                trace("超级权限 $uniq $controller $action", MysqlLog::INFO);
            }
            return;
        }
        if (!in_array($controller, self::limit_controller)) {
            if ($controller !== 'rpc') {
                trace("非限制路径 $uniq $controller", MysqlLog::LOG);
            }
            return;
        }
        if (array_key_exists($controller, self::except)
            && in_array($action, self::except[$controller])
        ) {
            trace("非限制方法 $uniq $controller $action", MysqlLog::LOG);
            return;
        }

        UserOper::valid_pc(request()->isAjax());
        if ($this->canView($controller)) {
            return;
        }
        if (strlen($uniq) > 0) {
            trace("禁止 $uniq $controller $action", MysqlLog::INFO);
        }
        if (request()->isAjax()) {
            $res = json(['msg' => '没有权限'], 400);
        } else {
            $res = redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        throw new HttpResponseException($res);
    }

    private function canView($controller)
    {
        if (in_array($controller, ['fame', 'analysis'])) {
            return true;
        }
        return false;
    }
}