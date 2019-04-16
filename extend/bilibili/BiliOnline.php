<?php

namespace bilibili;

use util\MysqlLog;

class BiliOnline extends BiliBase
{
    public function online()
    {
        $urlapi = $this->prefix . 'User/userOnlineHeart';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        if (!in_array($data['code'], [0, 65531])) {
            trace("online $res", MysqlLog::ERROR);
        }
    }

    public function getInfo()
    {
        $urlapi = $this->prefix . 'User/getUserInfo';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        return $res;
    }

    public function unknown_heart()//看起来没用 100 sec {"code":0,"msg":" ","message":" ","data":{"count":0,"open":0,"has_new":0}}
    {
        $urlapi = $this->prefix . 'feed/v1/feed/heartBeat?_=' . microtime(true);
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        if (!in_array($data['code'], [0, 65531])) {
            trace("unknown_heart $res", MysqlLog::ERROR);
        }
    }

    public function unknown_notice()//link 动态 100 sec {"code":0,"msg":" ","message":" ","data":{"num":0}}
    {
        $urlapi = $this->prefix . 'feed_svr/v1/feed_svr/notice';
        $data = [
            'csrf' => $this->csrf_token,
            'csrf_token' => $this->csrf_token
        ];
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id, http_build_query($data));
        $data = json_decode($res, true);
        if (!in_array($data['code'], [0, 65531])) {
            trace("unknown_notice $res", MysqlLog::ERROR);
        }
    }

    public function heart_gift_receive()
    {
        if ($this->lock('heart_gift_receive')) {
            return;
        }
        $urlapi = $this->prefix . 'gift/v2/live/heart_gift_receive?roomid=' . $this->room_id . '&area_v2_id=32';
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("heart_gift_receive $raw", MysqlLog::ERROR);
            return;
        }
        $data = $data['data'];
        $list = $data['gift_list'];
        if (is_array($list)) {
            foreach ($list as $item) {
                trace("{$item['gift_name']} {$item['day_num']}/{$item['day_limit']}", MysqlLog::INFO);
            }
        } else {
            switch ($data['heart_status']) {
                case 1:
                    return;
                case 0:
                    trace("empty heart_gift_receive", MysqlLog::INFO);
                    $this->lock('heart_gift_receive', $this->long_timeout());
                    return;
                default:
                    trace("heart_gift_receive {$data['heart_status']} {$data['heart_time']}", MysqlLog::ERROR);
            }
        }
    }

    private function heartbeat()
    {
        $urlapi = $this->prefix . 'eventRoom/index?ruid=' . $this->ruid;
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        trace("心跳 {$data['msg']}", MysqlLog::LOG);
    }

    public function freeGift()
    {
        if ($this->lock('free_gift')) {
            return;
        }
        $urlapi = $this->prefix . 'eventRoom/heart?roomid=' . $this->room_id;
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        $timeout = 590;
        if ($data['code'] === 0) {
            $gift = end($data['data']['gift']);
            trace("{$data['msg']}，礼物 {$gift['bagId']}（{$gift['num']}）", MysqlLog::INFO);
        } elseif ($data['code'] === -403 && $data['data']['heart'] === false) {
            $timeout = $this->long_timeout();
            trace("free gift empty {$data['msg']}", MysqlLog::ERROR);
        } elseif ($data['msg'] === '非法心跳') {
            $this->heartbeat();
        } else {
            if (false === strpos($raw, 'DOCTYPE')) {
                trace("奇怪 $raw", MysqlLog::ERROR);
            }
            //$this->heartbeat();
        }
        $this->lock('free_gift', $timeout);
    }
}
