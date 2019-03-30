<?php

namespace app\hanbj\behavior;

use hanbj\HBConfig;

class AccessControll
{
    const limit_controller = [
        'analysis',
        'daily',
        'error',
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
        $module = strtolower(request()->module());
        $controller = strtolower(request()->controller());
        $action = strtolower(request()->action());

        $uniq = session('unique_name');
        if ($uniq === HBConfig::CODER) {
            return;
        }

        if ($module !== 'hanbj') {
            return;
        }
        if (!in_array($controller, self::limit_controller)) {
            return;
        }
        if (array_key_exists($controller, self::except)
            && in_array($action, self::except[$controller])
        ) {
            return;
        }
    }
}