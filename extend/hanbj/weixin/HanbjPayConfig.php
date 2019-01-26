<?php

namespace hanbj\weixin;

use wxsdk\pay\WxPayConfigInterface;


class HanbjPayConfig extends WxPayConfigInterface
{
    public function GetAppId()
    {
        return config('hanbj_pay_appid');
    }

    public function GetMerchantId()
    {
        return config('hanbj_pay_mchid');
    }

    public function GetNotifyUrl()
    {
        return 'https://app.zxyqwe.com/hanbj/wxdaily/notify';
    }

    public function GetSignType()
    {
        return 'MD5';
    }

    public function GetProxy(&$proxyHost, &$proxyPort)
    {
    }

    public function GetReportLevenl()
    {
        return 1;
    }

    public function GetKey()
    {
        return config('hanbj_pay_key');
    }

    public function GetAppSecret()
    {
        return config('hanbj_secret');
    }

    public function GetSSLCertPath(&$sslCertPath, &$sslKeyPath)
    {
    }

}

