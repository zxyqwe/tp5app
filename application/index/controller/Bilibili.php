<?php

namespace app\index\controller;


use bilibili\BiliDanmu;
use bilibili\BiliOnline;
use bilibili\BiliSend;
use bilibili\BiliSilver;

class Bilibili
{
    public function _empty()
    {
        return json([], 404);
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
        define('TAG_TIMEOUT_EXCEPTION', true);
        $time = date("Y-m-d H:i:s");
        $bili = new BiliOnline();
        if ($bili->lock("Bili400")) {
            return json(['msg' => 'Bili400']);
        }
        if ($bili->lock('cookie')) {
            return json(['msg' => 'too fast', 'time' => $time]);
        }
        $bili->lock('cookie', 290);
        $bili->online();
        $bili->unknown_notice();
        $bili->unknown_heart();
        cache('bili_cron_user_past', cache('bili_cron_user'));
        $res = $bili->getInfo();
        cache('bili_cron_user', $res);
        cache('bili_cron_time', $time);
        //$bili->freeGift();
        //$bili->heart_gift_receive();
        $bili = new BiliSilver();
        $bili->silver();
        $bili = new BiliSend();
        $bili->send();
        return json(['msg' => 'ok', 'time' => $time]);
    }

    public function un()
    {
        define('TAG_TIMEOUT_EXCEPTION', true);
        $sk = input('post.sk');
        if (config('raffle_sk') !== $sk) {
            return json(['msg' => 'sk'], 400);
        }
        $bili = new BiliDanmu();
        if ($bili->lock("Bili400")) {
            return json(['msg' => 'Bili400']);
        }
        $id = input('get.id');
        $giftId = input('post.giftId');
        $real_roomid = input('post.real_roomid');
        switch ($id) {
            case '1':
                return $bili->unknown_raffle($real_roomid);
            case '2':
                return $bili->unknown_smallTV($real_roomid);
            case '3':
                return $bili->notice_any($giftId, $real_roomid, 'activity/v1/Raffle/notice?', 'unknown_raffle');
            case '4':
                return $bili->notice_any($giftId, $real_roomid, 'gift/v2/smalltv/notice?', 'unknown_smallTV');
        }
        return json(['msg' => 'id']);
    }
}
