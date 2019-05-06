<?php

namespace bilibili;

use util\MysqlLog;

class BiliSend extends BiliBase
{
    private function sign()
    {
        $urlapi = $this->prefix . 'sign/doSign';
        $raw = $this->bili_Get($urlapi, $this->room_id);
        $data = json_decode($raw, true);
        if ($data['code'] == -500) {
            return;
        }
        $urlapi = $this->prefix . 'gift/v2/live/receive_daily_bag';
        $raw = $this->bili_Get($urlapi, $this->room_id);
        $data = json_decode($raw, true);
        if (0 !== $data['code']) {
            trace('sendDaily ' . $raw, MysqlLog::ERROR);
        }
        $urlapi = $this->prefix . 'sign/GetSignInfo';
        $raw = $this->bili_Get($urlapi, $this->room_id);
        $data = json_decode($raw, true);
        if (!isset($data['data']) || !isset($data['data']['text']) || !isset($data['data']['specialText'])) {
            trace("GetSignInfo $raw", MysqlLog::ERROR);
            return;
        }
        trace("签到获得 {$data['data']['text']} {$data['data']['specialText']}", MysqlLog::INFO);
    }

    private function getSendGift()
    {
        if ($this->lock('getSendGift')) {
            return;
        }
        $this->sign();
        /*
        $urlapi = $this->prefix . 'giftBag/getSendGift';
        $raw = $this->bili_Post($urlapi, $this->room_id);
        $data = json_decode($raw, true);
        if (0 !== $data['code']) {
            trace("getSendGift $raw");
            return;
        }
        foreach ($data['data'] as $item) {
            trace("getSendGift {$item['giftTypeName']}");
        }
        */
        $this->lock('getSendGift', $this->long_timeout());
    }

    public function send()
    {
        if ($this->lock('send_gift')) {
            return;
        }
        if (!$this->bili_entry($this->room_id)) {
            return;
        }
        $this->getSendGift();
        $urlapi = $this->prefix . 'gift/v2/gift/bag_list';
        $raw = $this->bili_Get($urlapi, $this->room_id);
        $data = json_decode($raw, true);
        if (!isset($data['data']) || !isset($data['data']['list']) || !is_array($data['data']['list'])) {
            trace("send $raw", MysqlLog::ERROR);
            return;
        }
        $data = $data['data']['list'];
        foreach ($data as $vo) {
            $end = intval($vo['expire_at']);
            $period = $end - time();
            if ($period > 2 * 86400) {
                $inter = new \DateTime();
                $inter->setTimestamp($end);
                $period = $inter->diff(new \DateTime());
                trace("截止时间 " . date("Y-m-d H:i:s", $end) .
                    " 间隔时间 " . $period->format("%d天%H:%I:%S") .
                    " 跳过 {$vo['gift_num']} 个 {$vo['gift_name']}", MysqlLog::INFO);
                continue;
            }
            $payload = [
                'uid' => $this->uid,
                'gift_id' => $vo['gift_id'],
                'ruid' => $this->ruid,
                'gift_num' => $vo['gift_num'],
                'bag_id' => $vo['bag_id'],
                'platform' => 'pc',
                'biz_code' => 'live',
                'biz_id' => $this->room_id,
                'rnd' => mt_rand() % 10000000000,
                'storm_beat_id' => 0,
                'metadata' => '',
                'token' => '',
                'csrf' => $this->csrf_token,
                'csrf_token' => $this->csrf_token
            ];
            $urlapi = $this->prefix . 'gift/v2/live/bag_send';
            $raw = $this->bili_Post($urlapi, $this->room_id, http_build_query($payload));
            $res = json_decode($raw, true);
            if (0 !== $res['code']) {
                trace("投喂 {$this->csrf_token} $raw", MysqlLog::ERROR);
            } else {
                trace("成功投喂 {$vo['gift_num']} 个 {$vo['gift_name']}", MysqlLog::INFO);
            }
        }
        $this->lock('send_gift', $this->long_timeout());
    }
}
