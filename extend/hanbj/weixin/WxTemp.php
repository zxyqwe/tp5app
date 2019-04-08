<?php

namespace hanbj\weixin;


use util\MysqlLog;

class WxTemp
{
    const URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
    const temp_ids = [
        "WBIYdFZfjU7nE5QkL9wjYF6XUkUlQXKQblN5pvegtMw",//会费通知
        //    {{first.DATA}}
        //
        //    {{accountType.DATA}}：{{account.DATA}}
        //    充值金额：{{amount.DATA}}
        //    充值状态：{{result.DATA}}
        //    {{remark.DATA}}
        "pAg9VfUQYxgGfVmceEpw_AXiLPEXb7Ug4pamcG45d-A",//活动登记
        //    {{first.DATA}}
        //    认证详情：{{keyword1.DATA}}
        //    认证结果：{{keyword2.DATA}}
        //    {{remark.DATA}}
        "zz9SS28LbNafwLJW3WeWojrVH3qxfXsQsmXxlVU4JMk",
        //    {{first.DATA}}
        //
        //    订单信息:{{info.DATA}}
        //    取票密码:{{code.DATA}}
        //    开场时间:{{time.DATA}}
        //    卖品:{{product.DATA}}
        //
        //    {{remark.DATA}}
        "gvfqTpby_YxrY37eP4j_JPhW-LgffHofr2rPyPlyUso",
        //    {{first.DATA}}
        //    商品名称：{{keyword1.DATA}}
        //    交易金额：{{keyword2.DATA}}
        //    交易时间：{{keyword3.DATA}}
        //    商户单号：{{keyword4.DATA}}
        //    电子码：{{keyword5.DATA}}
        //    {{remark.DATA}}
        "_UAmJO3kH230039TEOgYtt179_KV8LANcl_XbnQgNK0",
        //    {{first.DATA}}
        //    申请内容：{{keyword1.DATA}}
        //    内容编号：{{keyword2.DATA}}
        //    申请用户：{{keyword3.DATA}}
        //    申请时间：{{keyword4.DATA}}
        //    {{remark.DATA}}
        "QF0vOzlB6BgjxmX1drxCXNVWJTNej18yLrY38XzWvfI",
        //    {{first.DATA}}
        //    物流公司：{{keyword1.DATA}}
        //    快递单号：{{keyword2.DATA}}
        //    快递费用：{{keyword3.DATA}}
        //    提交人：{{keyword4.DATA}}
        //    提交时间：{{keyword5.DATA}}
        //    {{remark.DATA}}
        "EOMnBdJ7V762zoVk0KpUmbB-oLWcAgqpfnKMvN7FrCA",
        //    {{first.DATA}}
        //    活动名称：{{keyword1.DATA}}
        //    活动时间：{{keyword2.DATA}}
        //    活动地址：{{keyword3.DATA}}
        //    {{remark.DATA}}
        "4QVJMeYGQrwblJah93CUVEIcHWqKbcC8l81FRQc2U48",
        //    {{first.DATA}}
        //    拍摄时间：{{keyword1.DATA}}
        //    顾客姓名：{{keyword2.DATA}}
        //    所选门店：{{keyword3.DATA}}
        //    门店地址：{{keyword4.DATA}}
        //    拍摄内容：{{keyword5.DATA}}
        //    {{remark.DATA}}
        "2wC2skga5l5LLA_ClBl_l8F3ChQYFiOkNayX5L81shw",
        //    {{first.DATA}}
        //
        //    演出名称：{{showname.DATA}}
        //    购票张数：{{ticket_qty.DATA}}
        //    演出时间：{{showtime.DATA}}
        //    {{remark.DATA}}
    ];

    private static function base($data, $log)
    {
        if (!isset($data['touser']) || !isset($data['template_id'])) {
            return 'WxTemp data missing';
        }
        $limit = "{$data['touser']}{$data['template_id']}";
        if (cache("?$limit")) {
            return 'WxTemp limit';
        }
        cache($limit, $limit, 86400);

        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = self::URL . $access;
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("ERR $log $raw", MysqlLog::ERROR);
            return $raw;
        }
        trace($log, MysqlLog::INFO);
        return 'ok';
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
        return self::base($data, $log);
    }
}