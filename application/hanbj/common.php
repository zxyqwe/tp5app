<?php

namespace app\hanbj;

include_once APP_PATH . 'wx.php';
include_once APP_PATH . 'hanbj/WxConfig.php';
include_once APP_PATH . 'WxPay.php';

class LogUtil
{
    public static function list_dir($dir, $name)
    {
        $result = ['text' => $name];
        $cdir = scandir($dir, SCANDIR_SORT_DESCENDING);
        if (!empty($cdir)) {
            $result['nodes'] = [];
        }
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result['nodes'][] = self::list_dir($dir . DIRECTORY_SEPARATOR . $value, $value);
                } else {
                    $result['nodes'][] = ['text' => explode('.', $value)[0]];
                }
            }
        }
        return $result;
    }
}