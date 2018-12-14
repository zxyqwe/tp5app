<?php

namespace hanbj\weixin;


class WxTemp
{
    const URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';

    private static function base($data, $log)
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = self::URL . $access;
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("ERR $log $raw");
        } else {
            trace($log);
        }
    }

    public static function notifyFee($openid, $uname, $fee, $cache_fee, $label)
    {
        if (strlen($openid) <= 10) {
            return;
        }
        $data = [
            "touser" => $openid,
            "template_id" => "WBIYdFZfjU7nE5QkL9wjYF6XUkUlQXKQblN5pvegtMw",
            "url" => "https://app.zxyqwe.com/hanbj/mobile",
            "topcolor" => "#FF0000",
            "data" => [
                "first" => [
                    "value" => "您好，您已成功进行北京汉服协会（筹）会员缴费。"
                ],
                "accountType" => [
                    "value" => '会员编号'
                ],
                'account' => [
                    'value' => $uname,
                    "color" => "#173177"
                ],
                'amount' => [
                    'value' => $fee . '元'
                ],
                'result' => [
                    'value' => '缴至' . $cache_fee,
                    "color" => "#173177"
                ],
                'remark' => [
                    'value' => '明细：' . $label . '。积分将在核实后到账，请稍后。'
                ]
            ]
        ];
        $log = implode(', ', [$openid, $uname, $fee, $cache_fee, $label]);
        self::base($data, $log);
    }

    public static function regAct($openid, $uname, $act)
    {
        if (strlen($openid) <= 10) {
            return;
        }
        $data = [
            "touser" => $openid,
            "template_id" => "pAg9VfUQYxgGfVmceEpw_AXiLPEXb7Ug4pamcG45d-A",
            "url" => "https://app.zxyqwe.com/hanbj/mobile",
            "topcolor" => "#FF0000",
            "data" => [
                "first" => [
                    "value" => "您好，您已成功进行北京汉服协会（筹）活动登记。"
                ],
                "keyword1" => [
                    "value" => $act
                ],
                'keyword2' => [
                    'value' => $uname . '-成功',
                    "color" => "#173177"
                ],
                'remark' => [
                    'value' => '积分将在核实后到账，请稍后。'
                ]
            ]
        ];
        $log = implode(', ', [$openid, $uname, $act]);
        self::base($data, $log);
    }

    public static function rpc($data, $log)
    {
        self::base($data, $log);
    }
}