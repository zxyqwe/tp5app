<?php

namespace bilibili;

class BiliDanmu extends BiliBase
{
    private function _unknown($real_roomid, $url, $key)
    {
        $urlapi = $this->prefix . $url . $real_roomid;
        $raw = $this->bili_Post($urlapi, $this->cookie, $real_roomid);
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("$key $raw");
            return json(['msg' => "1 $raw"], 400);
        }
        $data = $data['data'];
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
        $data = $this->_unknown($real_roomid, 'gift/v2/smalltv/check?roomid=', 'unknown_smallTV');
        $ret = [];
        foreach ($data as $item) {
            $this->_handle_smallTV($real_roomid, $item, $ret);
        }
        return json($ret);
    }

    private function _handle($real_roomid, $item, $key, $url, &$ret)
    {
        $payload = [
            'roomid' => $real_roomid,
            'raffleId' => $item['raffleId']
        ];
        $ret[] = $payload;
        $payload = http_build_query($payload);
        if ($this->lock("$key$payload")) {
            return;
        }
        $urlapi = $this->prefix . $url;
        if (false !== strpos($url, '?')) {
            $urlapi .= $payload;
            $postdata = false;
        } else {
            $postdata = $payload;
        }
        $raw = $this->bili_Post($urlapi, $this->cookie, $real_roomid, $postdata);
        $join = json_decode($raw, true);
        if (in_array($join['code'], [0, 65531])
            || false !== strpos($raw, '已加入')
            || false !== strpos($raw, '已结束')
            || false !== strpos($raw, '已经结束')
            || false !== strpos($raw, '访问被拒绝')
        ) {
            $this->lock("$key$payload", $this->long_timeout());
            return;
        }
        if (false !== strpos($raw, '不存在')) {
            return;
        }
        trace("$key " . json_encode($item) . $raw);
    }

    private function _handle_raffle($real_roomid, $item, &$ret)
    {
        $this->_handle($real_roomid, $item, 'unknown_raffle', 'activity/v1/Raffle/join', $ret);
    }

    private function _handle_smallTV($real_roomid, $item, &$ret)
    {
        $this->_handle($real_roomid, $item, 'unknown_smallTV', 'gift/v2/smalltv/join?', $ret);
    }

    public function notice_any($giftId, $real_roomid, $url, $key)
    {
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
        $raw = $this->bili_Post($urlapi, $this->cookie, $real_roomid);
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
                   || false !== strpos($data['msg'], '错过')) {
                    return json(['msg' => 'NOTHING']);
                }
                $data = $data['data'];
                if ($data['gift_num'] > 0) {
                    $data_for = "$key {$data['gift_num']} 个 {$data['gift_name']}";
                    if (!in_array($data['gift_name'], ['辣条', '小红包'])) {
                        trace($data_for);
                    }
                    return json(['msg' => $data_for]);
                }
                return json(['msg' => $raw]);
            default:
                trace("notice_any $raw");
                return json(['msg' => "1 $raw"], 400);
        }
    }
}
