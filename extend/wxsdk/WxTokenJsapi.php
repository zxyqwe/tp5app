<?php

namespace wxsdk;

use util\MysqlLog;
use util\TokenOper;

class WxTokenJsapi extends TokenOper
{
    protected function updateValue()
    {
        $access_token = new WxTokenAccess('HANBJ_ACCESS', $this->api, $this->sk);
        $access = $access_token->get();
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $access . '&type=jsapi';
        $raw = Curl_Get($url, 5);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("JsApi $raw", MysqlLog::ERROR);
            return;
        }
        trace("Weixin JsApi " . $res['ticket'], MysqlLog::LOG);
        $this->expire_time = time() + intval($res['expires_in']);
        $this->value = $res['ticket'];
        cache($this->cache_key,
            json_encode([
                'value' => $this->value,
                'expire_time' => $this->expire_time
            ]));
    }
}