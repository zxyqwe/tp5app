<?php

namespace app\hanbj\behavior;

use think\Debug;
use think\App;

class RecEnd
{
    /*
     * @param \think\Response
     */
    public function run(&$response)
    {
        if (defined('TAG_TIMEOUT_EXCEPTION')) {
            return;
        }
        $runtime = Debug::getUseTime();
        if ($runtime > 1.5) {
            App::$debug = true;
            Debug::remark('behavior_start', THINK_START_TIME);
            $ret = [
                'GET' => $_GET,
                'POST' => $_POST,
                'Files' => $_FILES,
                'Cookies' => $_COOKIE,
                'Session' => isset($_SESSION) ? $_SESSION : [],
                'Server' => $_SERVER,
                'Env' => $_ENV
            ];
            $ret = explode_dict($ret);
            foreach ($ret as $i) {
                trace($i, 'debug');
            }
        }
    }
}