<?php

namespace bilibili;

use util\MysqlLog;

class BiliDanmu extends BiliBase
{
    private function _unknown($real_roomid, $url, $key)
    {
        if (!$this->bili_entry($real_roomid)) {
            return [];
        }
        $data = [
            'csrf' => $this->csrf_token,
            'csrf_token' => $this->csrf_token
        ];
        $urlapi = $this->prefix . $url . $real_roomid;
        $raw = $this->bili_Post($urlapi, $real_roomid, http_build_query($data));
        $data = json_decode($raw, true);
        if ($data['code'] !== 0 || !isset($data['data'])) {
            trace("$url $key $raw", MysqlLog::ERROR);
            return json(['msg' => "1 $raw"], 400);
        }
        $data = $data['data'];
        if (isset($data['list'])) {
            $data = $data['list'];
        }
        return $data;
    }

    public function unknown_lottery()
    {
    }

    public function unknown_raffle($real_roomid)
    {
        $data = $this->_unknown($real_roomid, 'activity/v1/Raffle/check?roomid=', 'unknown_raffle');
        $ret = [];
        foreach ($data as $item) {
            $this->_handle_raffle($real_roomid, $item, $ret);
        }
        return json($ret);
    }

    public function unknown_smallTV($real_roomid)
    {
        $data = $this->_unknown($real_roomid, 'gift/v3/smalltv/check?roomid=', 'unknown_smallTV');
        $ret = [];
        foreach ($data as $item) {
            $this->_handle_smallTV($real_roomid, $item, $ret);
        }
        return json($ret);
    }

    private function _handle($real_roomid, $item, $key, $url, &$ret)
    {
        $payload_raw = [
            'roomid' => $real_roomid,
            'raffleId' => $item['raffleId'],
            'type' => 'Gift',
            'csrf_token' => $this->csrf_token,
            'csrf' => $this->csrf_token,
            'visit_id' => ''
        ];
        $payload = http_build_query($payload_raw);
        if ($this->lock("$key$payload")) {
            return;
        }
        if (rand(0, 100) > 30) {
            $this->lock("$key$payload", $this->long_timeout());
            return;
        }
        if (!$this->bili_entry($real_roomid)) {
            return;
        }
        $debounce_time = rand(600, 3600);
        $this->lock("debounce", $debounce_time);
        trace("Bili Debounce $debounce_time", MysqlLog::INFO);
        $urlapi = $this->prefix . $url;
        $raw = $this->bili_Post($urlapi, $real_roomid, $payload);
        $join = json_decode($raw, true);
        if (false !== strpos($raw, '访问被拒绝') || $join['code'] == 400) {
            trace("Bili400 $raw " . json_encode([$real_roomid, $item, $key, $url]), MysqlLog::ERROR);
            $this->lock("Bili400", $this->long_timeout());
            return;
        }
        if ($join['code'] === 0) {
            trace('可能有了 ' . json_encode([$item["time_wait"], $item["time"], $item["max_time"], $item["status"]]), MysqlLog::INFO);
            $this->lock("$key$payload", $this->long_timeout());
            $ret[] = $payload_raw;
            return;
        }
        if ($join['code'] === 65531
            || false !== strpos($raw, '已加入')
            || false !== strpos($raw, '已结束')
            || false !== strpos($raw, '已经结束')
        ) {
            $this->lock("$key$payload", $this->long_timeout());
            return;
        }
        if (false !== strpos($raw, '不存在')) {
            return;
        }
        trace("$key " . json_encode($item) . $raw, MysqlLog::ERROR);
    }

    private function _handle_raffle($real_roomid, $item, &$ret)
    {
        $this->_handle($real_roomid, $item, 'unknown_raffle', 'activity/v1/Raffle/join', $ret);
    }

    private function _handle_smallTV($real_roomid, $item, &$ret)
    {
        $this->_handle($real_roomid, $item, 'unknown_smallTV', 'gift/v3/smalltv/join?', $ret);
    }

    public function notice_any($giftId, $real_roomid, $url, $key)
    {
        if (!$this->bili_entry($real_roomid)) {
            return json(['msg' => 'FISH']);
        }
        $payload = [
            'roomid' => $real_roomid,
            'raffleId' => $giftId
        ];
        $payload = http_build_query($payload);
        if (!$this->lock("$key$payload")) {
            $ret = [];
            if ($key === 'unknown_raffle') {
                $this->_handle_raffle($real_roomid, ['raffleId' => $giftId], $ret);
            } elseif ($key === 'unknown_smallTV') {
                $this->_handle_smallTV($real_roomid, ['raffleId' => $giftId], $ret);
            } else {
                $ret[] = $payload;
            }
            return json(['msg' => 'ADD', 'data' => $ret]);
        }
        $urlapi = $this->prefix . $url . $payload;
        $raw = $this->bili_Post($urlapi, $real_roomid);
        if (false !== strpos($raw, '正在抽奖中')
            || false !== strpos($raw, '尚未开奖')
        ) {
            return json(['msg' => 'WAIT']);
        }
        $data = json_decode($raw, true);
        switch ($data['code']) {
            case -400:
                return json(['msg' => $raw], 400);
            case 0:
                $this->lock("$key$payload", -1);
                if (false !== strpos($data['msg'], '很遗憾')
                    || false !== strpos($data['msg'], '错过')
                ) {
                    return json(['msg' => 'NOTHING']);
                }
                $data = $data['data'];
                if ($data['gift_num'] > 0) {
                    $data_for = "$key {$data['gift_num']} 个 {$data['gift_name']}";
                    if (!in_array($data['gift_name'], ['辣条', '小红包'])) {
                        trace($data_for, MysqlLog::INFO);
                    }
                    return json(['msg' => $data_for]);
                }
                return json(['msg' => $raw]);
            default:
                trace("notice_any $raw", MysqlLog::ERROR);
                return json(['msg' => "1 $raw"], 400);
        }
    }
}
