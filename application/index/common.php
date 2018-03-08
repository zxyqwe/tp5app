<?php

namespace app\index;

use think\exception\HttpResponseException;

class BiliBase
{
    protected $token = '';
    protected $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36';
    protected $prefix = 'https://api.live.bilibili.com/';
    protected $cookie = '';
    protected $csrf_token = '';
    protected $room_id = 218;
    protected $ruid = 116683;
    protected $uid = 649681;
    private $curl;

    public function __construct()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->agent);

        $this->cookie = config('bili_cron_cookie');
        preg_match('/LIVE_LOGIN_DATA=(.{40})/', $this->cookie, $token);
        $this->token = isset($token[1]) ? $token[1] : '';
        preg_match('/bili_jct=(.{32})/', $this->cookie, $token);
        $this->csrf_token = isset($token[1]) ? $token[1] : '';
    }

    protected function bili_Post($url, $cookie, $room, $data = false, $sub = true, $post = true)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_COOKIE, $cookie);
        curl_setopt($this->curl, CURLOPT_POST, $post);
        if ($post) {
            if (false !== $data) {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, '');
            }
        }
        curl_setopt($this->curl, CURLOPT_REFERER, 'https://live.bilibili.com/' . $room);
        $return_str = curl_exec($this->curl);
        explode_curl($this->curl);
        if ($return_str === false) {
            $num = curl_errno($this->curl);
            $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($this->curl);
            if (!(false !== strpos($return_str, 'Timeout')
                || false !== strpos($return_str, 'SSL connect error')
                || false !== strpos($return_str, 'Empty reply')
            )
            ) {
                trace("url => $url, res => $return_str");
            }
            throw new HttpResponseException(json(['msg' => 'bili_Post ' . $return_str], 400));
        }
        if (false !== strpos($return_str, 'timeout')
            || false !== strpos($return_str, 'time-out')
            || false !== strpos($return_str, '系统繁忙')
        ) {
            throw new HttpResponseException(json(['msg' => 'bili_Post ' . $return_str], 400));
        }
        if ($sub && is_null(json_decode($return_str))) {
            $return_str = str_replace(["\r", "\n", "\t", "\f"], '', $return_str);
            $return_str = 'bili_Post 失败 ' . urlencode(substr($return_str, 0, 100));
        }
        return $return_str;
    }

    public function lock($name, $time = 0, $res = null)
    {
        $name = "bili_cron_$name";
        switch ($time) {
            case 0:
                return cache("?$name");
            case -1:
                cache($name, null);
                return null;
            case 1:
                if (null !== $res) {
                    cache($name, $res);
                    return $res;
                }
                return cache($name);
        }
        cache($name, $name, $time);
        return $name;
    }

    public function long_timeout()
    {
        $timeout = strtotime(date("Y-m-d")) + 25 * 3600 - time();
        return min(8 * 3600, $timeout);
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }
}

class BiliOnline extends BiliBase
{
    public function online()
    {
        $urlapi = $this->prefix . 'User/userOnlineHeart';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        if (!in_array($data['code'], [0, 65531])) {
            trace("online $res");
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
        if (!in_array($data['code'], [0])) {
            trace("unknown_notice $res");
        }
    }

    public function unknown_notice()//link 动态 100 sec {"code":0,"msg":" ","message":" ","data":{"num":0}}
    {
        $urlapi = $this->prefix . 'feed_svr/v1/feed_svr/notice';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id, ['csrf_token' => $this->csrf_token]);
        $data = json_decode($res, true);
        if (!in_array($data['code'], [0])) {
            trace("unknown_notice $res");
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
            trace("heart_gift_receive $raw");
            return;
        }
        $data = $data['data'];
        $list = $data['gift_list'];
        if (is_array($list)) {
            foreach ($list as $item) {
                trace("{$item['gift_name']} {$item['day_num']}/{$item['day_limit']}");
            }
        } else {
            switch ($data['heart_status']) {
                case 1:
                    return;
                case 0:
                    trace("empty heart_gift_receive");
                    $this->lock('heart_gift_receive', $this->long_timeout());
                    return;
                default:
                    trace("heart_gift_receive {$data['heart_status']} {$data['heart_time']}");
            }
        }
    }

    private function heartbeat()
    {
        $urlapi = $this->prefix . 'eventRoom/index?ruid=' . $this->ruid;
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        trace("心跳 {$data['msg']}");
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
            trace("{$data['msg']}，礼物 {$gift['bagId']}（{$gift['num']}）");
        } elseif ($data['code'] === -403 && $data['data']['heart'] === false) {
            $timeout = $this->long_timeout();
            trace("free gift empty {$data['msg']}");
        } elseif ($data['msg'] === '非法心跳') {
            $this->heartbeat();
        } else {
            if (false === strpos($raw, 'DOCTYPE')) {
                trace("奇怪 $raw");
            }
            //$this->heartbeat();
        }
        $this->lock('free_gift', $timeout);
    }
}

class BiliSend extends BiliBase
{
    private function sign()
    {
        $urlapi = $this->prefix . 'sign/doSign';
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        if ($data['code'] == -500) {
            return;
        }
        $urlapi = $this->prefix . 'gift/v2/live/receive_daily_bag';
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        if (0 !== $data['code']) {
            trace('sendDaily ' . $raw);
        }
        $urlapi = $this->prefix . 'sign/GetSignInfo';
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        trace("签到获得 {$data['data']['text']} {$data['data']['specialText']}");
    }

    private function getSendGift()
    {
        if ($this->lock('getSendGift')) {
            return;
        }
        $this->sign();
        /*
        $urlapi = $this->prefix . 'giftBag/getSendGift';
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
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
        $this->getSendGift();
        $urlapi = $this->prefix . 'gift/v2/gift/bag_list';
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        if (!isset($data['data']) || !isset($data['data']['list']) || !is_array($data['data']['list'])) {
            trace("send $raw");
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
                    " 跳过 {$vo['gift_num']} 个 {$vo['gift_name']}");
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
                'csrf_token' => $this->csrf_token
            ];
            $urlapi = $this->prefix . 'gift/v2/live/bag_send';
            $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id, http_build_query($payload));
            $res = json_decode($raw, true);
            if (0 !== $res['code']) {
                trace("投喂 {$this->csrf_token} $raw");
            } else {
                trace("成功投喂 {$vo['gift_num']} 个 {$vo['gift_name']}");
            }
        }
        $this->lock('send_gift', $this->long_timeout());
    }
}

class BiliSilver extends BiliBase
{
    private $OCR = array(
        '0' => '0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111111111111111111111',
        '1' => '0011111001111101111110111111011111101111111111111111111111111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111001111100111110011111',
        '2' => '0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111000000000011111100000000001111110000000001111110000000001111111000000000111111000000000111111000000000011111100000000011111100000000011111100000000001111110000000001111110000000000111110000000000111111000000000111111000000000011111000000000011111100000000001111100000000001111111111111111111111111111111111111111111111111111111111111111',
        '3' => '0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111000000000011111100000000011111110000000111111110000000111111110000000011111100000000001111111100000000011111111000000000011111110000000000111111000000000001111100000000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
        '4' => '00000001111110000000000011111100000000001111110000000000011111100000000000111110000000000011111100000000000111111000000000001111100000000000111111000000000001111110000000000011111000000000001111110000000000011111101111100000111110011111000011111100111110000111111001111100001111100011111000111111000111110001111110001111100011111000011111001111110000111110011111111111111111111111111111111111111111111111111111111111111111111000000000011111000000000000111110000000000001111100000000000011111000000000000111110000000000001111100',
        '5' => '1111111111111111111111111111111111111111111111111111111111111111111110000000000011111000000000001111100000000000111110000000000011111000000000001111100000000000111110000000000011111111111111101111111111111111111111111111111111111111111111110000000000011111000000000001111100000000000111110000000000011111000000000001111100000000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
        '6' => '0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000000000111110000000000011111000000000001111100000000000111111111111111011111111111111111111111111111111111111111111111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
        '7' => '1111111111111111111111111111111111111111111111111111111111111111111110000011111111111000001111111111100000111110111110000011111000000000011111100000000001111110000000000111110000000000011111000000000011111100000000001111110000000000111110000000000011111000000000011111100000000001111110000000000111111000000000011111000000000011111100000000001111110000000000111111000000000011111000000000011111100000000001111110000000000111111000000000011111000000000011111100000000001111110000000000111110000000',
        '8' => '0111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111100000111111111100000011111111110000001111111111100001111110111111111111110001111111111110000011111111110000011111111111100011111111111111011111110011111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111111',
        '9' => '1111111111111110111111111111111111111111111111111111111111111111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111100000011111111110000001111111111111111111111111111111111111111111111111111101111111111111110000000000011111000000000001111100000000000111110000000000011111111110000001111111111000000111111111100000011111111110000001111111111000000111111111111111111111111111111111111111111111111111110111111111111110',
        '+' => '00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000111110000000000001111100000000000011111000000000000111110000000000001111100000000000011111000000111111111111111111111111111111111111111111111111111111111111111111110000001111100000000000011111000000000000111110000000000001111100000000000011111000000000000111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        '-' => '000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000111111111111111111111111111111111111000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
    );

    public function silver()
    {
        if ($this->lock('day_empty')) {
            return;
        }
        $data = $this->silverTask();
        if (empty($data)) {
            return;
        }
        $data = json_decode($data, true);
        $start = $data['data']['time_start'];
        $end = $data['data']['time_end'];
        if (time() < $end) {
            return;
        }
        $captcha = $this->captcha();
        if ($captcha === false) {
            return;
        }
        $urlapi = $this->prefix . "freeSilver/getAward?time_start=$start&time_end=$end&captcha=$captcha";
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        if ($data['code'] === 0) {
            trace("领取银瓜子：{$data['data']['silver']} (+{$data['data']['awardSilver']})");
            $this->lock('silverTask', -1);
            $this->silverTask();
        } else {
            if (-903 === $data['code'] || false !== strpos($data['msg'], '过期')) {
                trace("领取失败：{$data['msg']}");
                $this->lock('silverTask', -1);
                $this->silverTask();
                return;
            }
            trace("领取失败：$res");
        }
    }

    private function silverTask()
    {
        if ($this->lock('silverTask')) {
            return $this->lock('silverTask', 1);
        }
        $urlapi = $this->prefix . 'lottery/v1/SilverBox/getCurrentTask';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        switch ($data['code']) {
            case -101:
                trace("silverTask $res");
                return '';
            case -10017:
                trace("day empty {$data['msg']}");
                $this->lock('day_empty', $this->long_timeout());
                return '';
        }
        return $this->lock('silverTask', 1, $res);
    }

    private function captcha()
    {
        $urlapi = $this->prefix . 'lottery/v1/SilverBox/getCaptcha?ts=' . time();
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("captcha $raw");
            return false;
        }
        $data = $data['data']['img'];
        $raw = substr($data, strpos($data, 'base64,') + 7);
        $raw = base64_decode($raw);
        $image = imagecreatefromstring($raw);
        if ($image === false) {
            trace("captcha image $raw");
            return false;
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $vis = [];
        $grey = [];
        for ($i = 0; $i < $height; $i++) {
            for ($j = 0; $j < $width; $j++) {
                $grey[$i][$j] = (imagecolorat($image, $j, $i) >> 16) & 0xFF;
            }
        }
        for ($i = 0; $i < $width; $i++) {
            $vis[$i] = 0;
        }
        for ($i = 0; $i < $height; $i++) {
            for ($j = 0; $j < $width; $j++) {
                $vis[$j] |= $grey[$i][$j] < 220;
            }
        }
        $result = '';
        for ($k = 0; $k < $width; $k++) {
            if ($vis[$k]) {
                $L = $R = $k;
                while ($vis[$R] == 1) {
                    $R++;
                }
                $str = '';
                for ($i = 4; $i <= 34; $i++) {
                    for ($j = $L; $j < $R; $j++) {
                        $str .= $grey[$i][$j] < 220 ? '1' : '0';
                    }
                }
                $max = 0;
                $ch = '';
                foreach ($this->OCR as $key => $vo) {
                    similar_text($str, $vo, $per);
                    if ($per > $max) {
                        $max = $per;
                        $ch = $key;
                    }
                }
                $result .= $ch;
                $k = $R;
            }
        }
        $ans = eval("return $result;");
        return $ans;
    }
}

class BiliDanmu extends BiliBase
{
    public function unknown_smallTV($real_roomid)
    {
        $urlapi = $this->prefix . 'gift/v2/smalltv/check?roomid=' . $real_roomid;
        $raw = $this->bili_Post($urlapi, $this->cookie, $real_roomid);
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("unknown_smallTV $raw");
            return json(['msg' => "1 $raw"], 400);
        }
        $data = $data['data'];
        $ret = [];
        foreach ($data as $item) {
            $this->_handle_smallTV($real_roomid, $item, $ret);
        }
        return json($ret);
    }

    private function _handle_smallTV($real_roomid, $item, &$ret)
    {
        $payload = [
            'roomid' => $real_roomid,
            'raffleId' => $item['raffleId']
        ];
        $ret[] = $payload;
        $payload = http_build_query($payload);
        if ($this->lock("unknown_smallTV$payload")) {
            return;
        }
        $urlapi = $this->prefix . 'gift/v2/smalltv/join?' . $payload;
        $raw = $this->bili_Post($urlapi, $this->cookie, $real_roomid);
        $join = json_decode($raw, true);
        if (in_array($join['code'], [0, 65531])
            || false !== strpos($raw, '已加入')
            || false !== strpos($raw, '已结束')
            || false !== strpos($raw, '已经结束')
        ) {
            $this->lock("unknown_smallTV$payload", $this->long_timeout());
            return;
        }
        if (false !== strpos($raw, '不存在')) {
            return;
        }
        trace('unknown_smallTV' . json_encode($item) . $raw);
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
                if (false !== strpos($data['msg'], '很遗憾')) {
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

    public function unknown_lottery()
    {
    }

    public function unknown_raffle($real_roomid)
    {
        $urlapi = $this->prefix . 'activity/v1/Raffle/check?roomid=' . $real_roomid;
        $raw = $this->bili_Post($urlapi, $this->cookie, $real_roomid);
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("unknown_raffle $raw");
            return json(['msg' => "1 $raw"], 400);
        }
        $data = $data['data'];
        $ret = [];
        foreach ($data as $item) {
            $this->_handle_raffle($real_roomid, $item, $ret);
        }
        return json($ret);
    }

    private function _handle_raffle($real_roomid, $raffle, &$ret)
    {
        $payload = [
            'roomid' => $real_roomid,
            'raffleId' => $raffle['raffleId']
        ];
        $ret[] = $payload;
        $payload = http_build_query($payload);
        if ($this->lock("unknown_raffle$payload")) {
            return;
        }
        $urlapi = $this->prefix . 'activity/v1/Raffle/join';
        $raw = $this->bili_Post($urlapi, $this->cookie, $real_roomid, $payload);
        $join = json_decode($raw, true);
        if (in_array($join['code'], [0, 65531])
            || false !== strpos($raw, '已加入')
            || false !== strpos($raw, '已结束')
            || false !== strpos($raw, '已经结束')
        ) {
            $this->lock("unknown_raffle$payload", $this->long_timeout());
            return;
        }
        if (false !== strpos($raw, '不存在')) {
            return;
        }
        trace('unknown_raffle' . json_encode($raffle) . $raw);
    }
}

class GeoHelper
{
    private $ak = '';

    public function __construct()
    {
        $this->ak = config('baidu_ak');
    }

    public function getPos($pos)
    {
        $prefix = 'baidu_' . $pos;
        if (cache('?' . $prefix)) {
            return cache($prefix);
        }
        if (cache('?baidu_limit_geopos')) {
            return json_encode(['msg' => 1]);
        }
        $urlapi = "http://api.map.baidu.com/geocoder/v2/?address=$pos&output=json&city=北京市&ak={$this->ak}";
        $raw = Curl_Get($urlapi);
        $data = json_decode($raw, true);
        if ($data['status'] === 0) {
            cache($prefix, $raw);
        } elseif ($data['status'] === 302) {
            cache('baidu_limit_geopos', 'baidu_limit_geopos', 8 * 3600);
            return json_encode(['msg' => 1]);
        }
        return $raw;
    }
}
