<?php

namespace util;


class GeneralRet
{

    const ERR_ARRAY = [
        'PAY_ID_INVALID' => 9977, // 支付订单号错误
        'NAME_VARIFY_WX_FAIL' => 9978,  // 实名认证微信出错
        'NAME_VARIFY_DONE_BEFORE' => 9979, // 实名认证成功做过
        'NAME_VARIFY_NAME' => 9980, // 实名认证真名不对
        'NAME_VARIFY_FEE' => 9981, // 实名认证金额不对
        'PAY_RECORD' => 9982, // 记录支付请求出错
        'PAY_FEE_INVALID' => 9983, // 支付的金额不对
        'PARAMS_MISSING' => 9984, // 接口要求的参数不存在
        'UNIQUE_NAME_INVALID' => 9985, // 会员编号不对
        'OPER_OWE_FEE' => 9986,  // 操作者欠费了
        'OPER_LOCKED' => 9987, // 操作者是黑名单
        'USER_OWE_FEE' => 9988, // 被操作的用户欠费了
        'USER_LOCKED' => 9989, // 被操作的用户是黑名单
        'ACTIVITY_ERR' => 9990, // 活动名称格式不对
        'BONUS_ERR' => 9991, // 登记活动的积分不对
        'TEMP_SEND_ERR' => 9992, // 发送模板消息的时候，发生错误
        'REQUIRE_SUBSCRIBE' => 9993, // 这个人没有关注，无法进行下一步操作
        'TEMP_ID_WRONG' => 9994, // 模板ID不对
        'PEOPLE_NOT_FOUND' => 9995, // 查无此人，通常是unionID没见过
        'PAGE_NOT_EXISTS' => 9996, // 接口不存在
        'SIGN_DIFF' => 9997, // 签名不对
        'TS_DIFF_HUGE' => 9998, // 时间戳相差太大
        'EMPTY_POST_BODY' => 9999 // post请求没有body
    ];
    const OK_ARRAY = [
        'SUCCESS' => 0, // 成功
        'DUPLICATE_PAY' => 1, // 重复的订单，不需要再记录，之前曾经记录过
        'DUPLICATE_ACTIVITY' => 2, // 重复登记的活动，不需要再记录，之前曾经记录过
    ];

    public static function __callStatic($name, $arguments)
    {
        $data_ = ['desc' => $name];
        if (array_key_exists($name, self::OK_ARRAY)) {
            $data_['msg'] = 'ok';
            $data_['code'] = intval(self::OK_ARRAY[$name]);
        } else if (array_key_exists($name, self::ERR_ARRAY)) {
            $data_['msg'] = 'err';
            $data_['code'] = intval(self::ERR_ARRAY[$name]);
        } else {
            $data_['msg'] = 'err';
            $data_['code'] = -1;
        }
        return $data_;
    }
}