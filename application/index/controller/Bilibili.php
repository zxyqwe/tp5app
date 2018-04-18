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
        $bili->ocr("data:image/jpeg;base64,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");
        return;
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
