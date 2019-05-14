<?php

namespace wxsdk;

use util\MysqlLog;
use util\TokenOper;

class WxTokenAccess extends TokenOper
{
    protected function updateValue()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->api . '&secret=' . $this->sk;
        $raw = Curl_Get($url, 5);
        $res = json_decode($raw, true);
        if (!isset($res['access_token']) || !isset($res['expires_in'])) {
            trace("WX_access $raw", MysqlLog::ERROR);
            return;
        }
        trace("Weixin Access " . $res['access_token'], MysqlLog::DEBUG);
        $this->expire_time = time() + intval($res['expires_in']);
        $this->value = $res['access_token'];
        cache($this->cache_key,
            json_encode([
                'value' => $this->value,
                'expire_time' => $this->expire_time
            ]));
    }
}