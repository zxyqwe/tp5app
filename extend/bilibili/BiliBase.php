<?php

namespace bilibili;

use think\exception\HttpResponseException;
use util\MysqlLog;

class BiliBase
{
    protected $token = '';
    protected $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36';
    protected $prefix = 'https://api.live.bilibili.com/';
    protected $cookie = '';
    protected $csrf_token = '';
    protected $room_id = 5294;
    protected $ruid = 116683;
    protected $uid = 649681;
    private $curl;

    public function __construct()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);

        $this->cookie = self::getCookies();
        curl_setopt($this->curl, CURLOPT_COOKIE, $this->cookie);

        preg_match('/LIVE_LOGIN_DATA=(.{40})/', $this->cookie, $token);
        $this->token = isset($token[1]) ? $token[1] : '';
        preg_match('/bili_jct=(.{32})/', $this->cookie, $token);
        $this->csrf_token = isset($token[1]) ? $token[1] : '';
    }

    public static function getCookies($set = false)
    {
        $cache_key = 'bili_cron_cookie_real_value';
        if ($set) {
            cache($cache_key, $set);
            return $set;
        } else {
            if (cache("?$cache_key")) {
                return cache($cache_key);
            }
            return config($cache_key);
        }
    }

    protected function bili_Get($url, $room)
    {
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        return $this->bili_curl($url, $room);
    }

    protected function bili_Post($url, $room, $data = false)
    {
        curl_setopt($this->curl, CURLOPT_POST, true);
        if (false !== $data) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, '');
        }
        return $this->bili_curl($url, $room);
    }

    private function bili_curl($url, $room)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_REFERER, 'https://live.bilibili.com/' . $room);
        $return_str = curl_exec($this->curl);
        $c_info = explode_curl($this->curl);
        if ($return_str === false) {
            $num = curl_errno($this->curl);
            $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($this->curl);
            if (!(false !== strpos($return_str, 'Timeout')
                || false !== strpos($return_str, 'SSL connect error')
                || false !== strpos($return_str, 'Empty reply')
            )
            ) {
                trace("url => $url, res => $return_str", MysqlLog::ERROR);
            }
            throw new HttpResponseException(json(['msg' => 'bili_Post ' . $return_str], 400));
        }
        if (false !== strpos($return_str, 'timeout')
            || false !== strpos($return_str, 'time-out')
            || false !== strpos($return_str, '系统繁忙')
        ) {
            throw new HttpResponseException(json(['msg' => 'bili_Post ' . $return_str], 400));
        }
        if (true && is_null(json_decode($return_str))) {
            $return_str = str_replace(["\r", "\n", "\t", "\f"], '', $return_str);
            $return_str = 'bili_Post 失败 ' . urlencode(substr($return_str, 0, 100));
        }
        if (false !== strpos($return_str, 'token')) {
            if (isset($c_info['request_header'])) {
                trace("CSRF {$this->csrf_token} Req " . json_encode($c_info['request_header']), MysqlLog::ERROR);
            }
        }
        return $return_str;
    }

    protected function bili_entry($rid)
    {
        $urlapi = 'https://live.bilibili.com/' . $rid;
        $this->bili_Post($urlapi, $rid);

        $data = [
            'csrf' => $this->csrf_token,
            'csrf_token' => $this->csrf_token
        ];
        $urlapi = $this->prefix . 'room/v1/Room/room_init?id=' . $rid;
        $raw = $this->bili_Post($urlapi, $rid, http_build_query($data));
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("钓鱼 $raw", MysqlLog::ERROR);
            return false;
        }
        $data = $data['data'];
        if ($data['encrypted'] || $data['is_hidden'] || $data['is_locked']) {
            trace("钓鱼 $raw", MysqlLog::ERROR);
            return false;
        }

        $urlapi = $this->prefix . 'room/v1/Room/room_entry_action';
        $payload = [
            'room_id' => $rid,
            'platform' => 'pc',
            'csrf' => $this->csrf_token,
            'csrf_token' => $this->csrf_token
        ];
        $raw = $this->bili_Post($urlapi, $rid, http_build_query($payload));
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("历史记录 $raw", MysqlLog::ERROR);
            return false;
        }

        return true;
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
                    cache($name, $res, $this->long_timeout());
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
