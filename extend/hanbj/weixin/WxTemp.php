<?php

namespace hanbj\weixin;


use hanbj\HBConfig;
use think\Db;
use util\MysqlLog;
use util\ValidateTimeOper;

class WxTemp
{
    const URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
    const temp_ids = [
        "2wC2skga5l5LLA_ClBl_l8F3ChQYFiOkNayX5L81shw",
        //    {{first.DATA}}
        //
        //    演出名称：{{showname.DATA}}
        //    购票张数：{{ticket_qty.DATA}}
        //    演出时间：{{showtime.DATA}}
        //    {{remark.DATA}}
        "4QVJMeYGQrwblJah93CUVEIcHWqKbcC8l81FRQc2U48",
        //    {{first.DATA}}
        //    拍摄时间：{{keyword1.DATA}}
        //    顾客姓名：{{keyword2.DATA}}
        //    所选门店：{{keyword3.DATA}}
        //    门店地址：{{keyword4.DATA}}
        //    拍摄内容：{{keyword5.DATA}}
        //    {{remark.DATA}}
        "EOMnBdJ7V762zoVk0KpUmbB-oLWcAgqpfnKMvN7FrCA",
        //    {{first.DATA}}
        //    活动名称：{{keyword1.DATA}}
        //    活动时间：{{keyword2.DATA}}
        //    活动地址：{{keyword3.DATA}}
        //    {{remark.DATA}}
        "GO9x4kW5Hm8gS3t8NLNrZXbKSdH7HdaNOS6aLUf-yFo",
        //    {{first.DATA}}
        //    活动名称：{{keyword1.DATA}}
        //    活动日期：{{keyword2.DATA}}
        //    预约时间：{{keyword3.DATA}}
        //    活动地点：{{keyword4.DATA}}
        //    参加人数：{{keyword5.DATA}}
        //    {{remark.DATA}}
        "QF0vOzlB6BgjxmX1drxCXNVWJTNej18yLrY38XzWvfI",
        //    {{first.DATA}}
        //    物流公司：{{keyword1.DATA}}
        //    快递单号：{{keyword2.DATA}}
        //    快递费用：{{keyword3.DATA}}
        //    提交人：{{keyword4.DATA}}
        //    提交时间：{{keyword5.DATA}}
        //    {{remark.DATA}}
        "WBIYdFZfjU7nE5QkL9wjYF6XUkUlQXKQblN5pvegtMw", //会费通知
        //    {{first.DATA}}
        //
        //    {{accountType.DATA}}：{{account.DATA}}
        //    充值金额：{{amount.DATA}}
        //    充值状态：{{result.DATA}}
        //    {{remark.DATA}}
        "XgXKHJzWfVHAub63HOtUnPai-eiQCOL76kwOrtGA5jY", // 待办提醒
        // {{first.DATA}}
        // 业务名称：{{keyword1.DATA}}
        // 业务内容：{{keyword2.DATA}}
        // {{remark.DATA}}
        "ZztiVmh-pB6jtAewRkugjGbpi-o043eho-Tuz440K5E",
        //    {{first.DATA}}
        //    手机号码：{{keyword1.DATA}}
        //    功能类型：{{keyword2.DATA}}
        //    时间：{{keyword3.DATA}}
        //    {{remark.DATA}}
        "_UAmJO3kH230039TEOgYtt179_KV8LANcl_XbnQgNK0",
        //    {{first.DATA}}
        //    申请内容：{{keyword1.DATA}}
        //    内容编号：{{keyword2.DATA}}
        //    申请用户：{{keyword3.DATA}}
        //    申请时间：{{keyword4.DATA}}
        //    {{remark.DATA}}
        "gvfqTpby_YxrY37eP4j_JPhW-LgffHofr2rPyPlyUso",
        //    {{first.DATA}}
        //    商品名称：{{keyword1.DATA}}
        //    交易金额：{{keyword2.DATA}}
        //    交易时间：{{keyword3.DATA}}
        //    商户单号：{{keyword4.DATA}}
        //    电子码：{{keyword5.DATA}}
        //    {{remark.DATA}}
        "pAg9VfUQYxgGfVmceEpw_AXiLPEXb7Ug4pamcG45d-A", //活动登记
        //    {{first.DATA}}
        //    认证详情：{{keyword1.DATA}}
        //    认证结果：{{keyword2.DATA}}
        //    {{remark.DATA}}
        "rH5w5wCf_Y0CphLuXBSrYpgYnck8-W6dJXFcqDMjv20",
        //    {{first.DATA}}
        //    预约内容：{{keyword1.DATA}}
        //    开始时间：{{keyword2.DATA}}
        //    {{remark.DATA}}
        "sWZRlzL0qD9gpxqAIoriGXWNvSgu5m3kuc5aeRqsJhk",
        //    {{first.DATA}}
        //    手机号码：{{keyword1.DATA}}
        //    功能类型：{{keyword2.DATA}}
        //    时间：{{keyword3.DATA}}
        //    {{remark.DATA}}
        "vKlOm8phKGMC74ndwnk9LPQTOguurnCCa9R3Wb_KReY",
        //    {{first.DATA}}
        //    项目：{{keyword1.DATA}}
        //    时间：{{keyword2.DATA}}
        //    门店：{{keyword3.DATA}}
        //    地址：{{keyword4.DATA}}
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
    ];

    private static function base($data, $log, $wx_limit = true)
    {
        if (!isset($data['touser']) || !isset($data['template_id'])) {
            return 'WxTemp data missing';
        }
        $limit = "{$data['touser']}{$data['template_id']}";
        if ($wx_limit && cache("?$limit")) {
            return 'WxTemp limit';
        }
        cache($limit, $limit, 600);

        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = self::URL . $access;
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("ERR $log $raw", MysqlLog::ERROR);
            cache($limit, $limit, 60); // 缩短到一分钟
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
        self::base($data, $log, false);
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
        self::base($data, $log, false);
    }

    public static function rpc($data, $log)
    {
        return self::base($data, $log);
    }

    public static function notifyStat($type, $data)
    {
        $cache_key = "notifyStat$type";
        if (cache("?$cache_key")) {
            return;
        }
        cache($cache_key, $cache_key, 43200);
        $openid = Db::table("member")
            ->where(['unique_name' => HBConfig::CODER])
            ->field(['openid'])
            ->find();
        $openid = $openid['openid'];
        $data = [
            "touser" => $openid,
            "template_id" => "XgXKHJzWfVHAub63HOtUnPai-eiQCOL76kwOrtGA5jY",
            "url" => "https://app.zxyqwe.com/hanbj/mobile",
            "topcolor" => "#FF0000",
            "data" => [
                "first" => [
                    "value" => "统计信息提醒"
                ],
                "keyword1" => [
                    "value" => HBConfig::CODER . " 的统计信息提醒"
                ],
                'keyword2' => [
                    'value' => strval($data),
                    "color" => "#173177"
                ],
                'remark' => [
                    'value' => '请尽快处理'
                ]
            ]
        ];
        self::base($data, "统计信息提醒");
    }

    public static function notifyTodo($openid, $uname, $num)
    {
        if (strlen($openid) <= 10) {
            return;
        }
        $hour_now = date('H');
        if (!ValidateTimeOper::IsDayUp()) {
            return;
        }
        $cache_key = "TodonoticeAny$openid";
        if (cache("?$cache_key")) {
            return;
        }
        $data = [
            "touser" => $openid,
            "template_id" => "XgXKHJzWfVHAub63HOtUnPai-eiQCOL76kwOrtGA5jY",
            "url" => "https://app.zxyqwe.com/hanbj/mobile/#todo",
            "topcolor" => "#FF0000",
            "data" => [
                "first" => [
                    "value" => "待办提醒"
                ],
                "keyword1" => [
                    "value" => "$uname 的待办提醒"
                ],
                'keyword2' => [
                    'value' => "当前 $num 未处理",
                    "color" => "#173177"
                ],
                'remark' => [
                    'value' => '请尽快处理，点击本消息跳转'
                ]
            ]
        ];
        $log = "待办提醒 " . implode(', ', [$openid, $uname, $num]);
        self::base($data, $log);
        cache($cache_key, $cache_key, 86400);
    }
}
