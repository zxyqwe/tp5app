<?php

namespace util;


class GeneralRet
{

    const ERR_ARRAY = [
        'NAME_VARIFY_AUTO_AUTH' => 8876,
        'NAME_VARIFY_DUPLICATE' => 8877,
        'NAME_VARIFY_RUNNING' => 8878,
        'NAME_VARIFY_NAME' => 8879,
        'NAME_VARIFY_FEE' => 8880,
        'PAY_RECORD' => 8881,
        'PAY_FEE_INVALID' => 8882,
        'PARAMS_MISSING' => 8883,
        'DUPLICATE_ACTIVITY' => 8884,
        'UNIQUE_NAME_INVALID' => 8885,
        'OPER_OWE_FEE' => 8886,
        'OPER_LOCKED' => 8887,
        'USER_OWE_FEE' => 8888,
        'USER_LOCKED' => 8889,
        'ACTIVITY_ERR' => 9990,
        'BONUS_ERR' => 9991,
        'TEMP_SEND_ERR' => 9992,
        'REQUIRE_SUBSCRIBE' => 9993,
        'TEMP_ID_WRONG' => 9994,
        'PEOPLE_NOT_FOUND' => 9995,
        'PAGE_NOT_EXISTS' => 9996,
        'SIGN_DIFF' => 9997,
        'TS_DIFF_HUGE' => 9998,
        'EMPTY_POST_BODY' => 9999
    ];
    const OK_ARRAY = [
        'SUCCESS' => 0,
        'DUPLICATE_PAY' => 1,
    ];

    public static function __callStatic($name, $arguments)
    {
        $data_ = ['desc' => $name];
        if (in_array($name, self::OK_ARRAY)) {
            $data_['msg'] = 'ok';
            $data_['code'] = intval(self::OK_ARRAY[$name]);
        } else if (in_array($name, self::ERR_ARRAY)) {
            $data_['msg'] = 'err';
            $data_['code'] = intval(self::ERR_ARRAY[$name]);
        } else {
            $data_['msg'] = 'err';
            $data_['code'] = -1;
        }
        return $data_;
    }
}