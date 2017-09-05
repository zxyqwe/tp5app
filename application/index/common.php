<?php

namespace app\index;

use think\exception\HttpResponseException;

class BiliHelper
{
    private $prefix = 'https://api.live.bilibili.com/';
    private $cookie = '';
    private $room_id = 218;
    private $ruid = 116683;
    private $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36';
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

    public function __construct()
    {
        $this->cookie = config('bili_cron_cookie');
    }

    public function online()
    {
        $urlapi = $this->prefix . 'User/userOnlineHeart';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        if ($data['code'] !== 0) {
            trace($res);
        }
    }

    public function getInfo()
    {
        $urlapi = $this->prefix . 'User/getUserInfo';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        return $res;
    }

    public function freeGift()
    {
        if (cache('?bili_cron_free_gift')) {
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
            $timeout = 8 * 3600;
            trace("free gift empty {$data['msg']}");
        } elseif ($data['msg'] === '非法心跳') {
            $urlapi = $this->prefix . 'eventRoom/index?ruid=' . $this->ruid;
            $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
            $data = json_decode($raw, true);
            trace("心跳 {$data['msg']}");
        } else {
            trace($data['msg']);
        }
        cache('bili_cron_free_gift', 'bili_cron_free_gift', $timeout);
    }

    public function silver()
    {
        if (cache('?bili_cron_day_empty')) {
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
        $urlapi = $this->prefix . "freeSilver/getAward?time_start=$start&time_end=$end&captcha=$captcha";
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        if ($data['code'] === 0) {
            trace("领取银瓜子：{$data['data']['silver']}(+{$data['data']['awardSilver']})");
            cache('bili_cron_silverTask', null);
            $this->silverTask();
        } else {
            if (false !== strstr($data['msg'], '过期')) {
                trace("领取失败：{$data['msg']}");
                cache('bili_cron_silverTask', null);
                $this->silverTask();
            } else {
                trace("领取失败：$res");
            }
        }
    }

    private function silverTask()
    {
        if (cache('?bili_cron_silverTask')) {
            return cache('bili_cron_silverTask');
        }
        $urlapi = $this->prefix . 'FreeSilver/getCurrentTask';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        switch ($data['code']) {
            case -101:
                trace($res);
                return '';
            case -10017:
                trace("day empty {$data['msg']}");
                cache('bili_cron_day_empty', 'bili_cron_day_empty', 8 * 3600);
                return '';
        }
        $start = date("Y-m-d H:i:s", $data['data']['time_start']);
        $end = date("Y-m-d H:i:s", $data['data']['time_end']);
        $str = "领取宝箱，{$data['data']['silver']}瓜子，{$data['data']['minute']}分钟，$start --> $end";
        trace($str);
        cache('bili_cron_silverTask', $res);
        return cache('bili_cron_silverTask');
    }

    private function captcha()
    {
        $urlapi = $this->prefix . 'freeSilver/getCaptcha?ts=' . time();
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $image = imagecreatefromstring($raw);
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
        trace("(～￣▽￣)～ $result = $ans");
        return $ans;
    }

    private function bili_Post($url, $cookie, $room)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_REFERER, 'http://live.bilibili.com/' . $room);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->agent);
        $return_str = curl_exec($curl);
        if ($return_str === false) {
            $num = curl_errno($curl);
            $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($curl);
            trace(['url' => $url, 'res' => $return_str]);
            throw new HttpResponseException(json(['msg' => 'bili_Post ' . $return_str]));
        }
        curl_close($curl);
        return $return_str;
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