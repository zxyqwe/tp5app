<?php

namespace app\index;
class BiliHelper
{
    private $prefix = 'https://api.live.bilibili.com/';
    private $cookie = '';

    public function __construct()
    {
        $this->cookie = config('bili_cron_cookie');
    }

    public function online()
    {
        $urlapi = $this->prefix . 'User/userOnlineHeart';
        $res = bili_Post($urlapi, $this->cookie, 218);
        $res = json_decode($res, true);
        if ($res['code'] !== 0) {
            trace(json_encode($res));
        }
    }

    public function getInfo()
    {
        $urlapi = $this->prefix . 'User/getUserInfo';
        $res = bili_Post($urlapi, $this->cookie, 218);
        return $res;
    }
}