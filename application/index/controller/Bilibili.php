<?php

namespace app\index\controller;

class Bilibili
{
    public function _empty()
    {
        return '';
    }

    public function index()
    {
        $time = date("Y-m-d H:i:s");
        return json([
            'past' => cache('cron_user_past'),
            'cur' => cache('cron_user'),
            'time' => $time
        ]);
    }

    public function cron()
    {
        $time = date("Y-m-d H:i:s");
        if (cache('?cron_cookie')) {
            return json(['msg' => 'too fast', 'time' => $time]);
        }
        cache('cron_cookie', 'cron_cookie', 290);
        $cookie = config('cron_cookie');
        $urlapi = 'https://api.live.bilibili.com/User/userOnlineHeart';
        $res = bili_Post($urlapi, $cookie, 218);
        $res = json_decode($res, true);
        if ($res['code'] !== 0) {
            trace(json_encode($res));
        }
        cache('cron_user_past', cache('cron_user'));
        $urlapi = 'http://live.bilibili.com/User/getUserInfo';
        $res = bili_Post($urlapi, $cookie, 218);
        cache('cron_user', $res);
        return json(['msg' => 'ok', 'time' => $time]);
    }
}
