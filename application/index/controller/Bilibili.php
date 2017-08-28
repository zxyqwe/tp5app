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
        $past = cache('bili_cron_user_past');
        $cur = cache('bili_cron_user');
        $past = json_decode($past, true);
        $cur = json_decode($cur, true);
        $past_intimacy = 0;
        $cur_intimacy = 0;
        if (isset($past['data']) && isset($past['data']['user_intimacy'])) {
            $past_intimacy = $past['data']['user_intimacy'];
        }
        if (isset($cur['data']) && isset($cur['data']['user_intimacy'])) {
            $cur_intimacy = $cur['data']['user_intimacy'];
        }
        $time = date("Y-m-d H:i:s");
        $cron_time = cache('bili_cron_time');
        return json([
            'past' => $past_intimacy,
            'cur' => $cur_intimacy,
            'time' => $time,
            'cron' => $cron_time
        ]);
    }

    public function cron()
    {
        $time = date("Y-m-d H:i:s");
        if (cache('?bili_cron_cookie')) {
            return json(['msg' => 'too fast', 'time' => $time]);
        }
        cache('bili_cron_cookie', 'bili_cron_cookie', 290);
        $cookie = config('bili_cron_cookie');
        $urlapi = 'https://api.live.bilibili.com/User/userOnlineHeart';
        $res = bili_Post($urlapi, $cookie, 218);
        $res = json_decode($res, true);
        if ($res['code'] !== 0) {
            trace(json_encode($res));
        }
        cache('bili_cron_user_past', cache('bili_cron_user'));
        $urlapi = 'https://api.live.bilibili.com/User/getUserInfo';
        $res = bili_Post($urlapi, $cookie, 218);
        cache('bili_cron_user', $res);
        cache('bili_cron_time', $time);
        return json(['msg' => 'ok', 'time' => $time]);
    }
}
