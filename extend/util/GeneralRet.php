<?php

namespace util;


class GeneralRet
{

    const ERR_ARRAY = [
        'NAME_VARIFY_AUTO_AUTH' => 9976,
        'NAME_VARIFY_DUPLICATE' => 9977,
        'NAME_VARIFY_RUNNING' => 9978,
        'NAME_VARIFY_NAME' => 9979,
        'NAME_VARIFY_FEE' => 9980,
        'PAY_RECORD' => 9981,
        'PAY_FEE_INVALID' => 9982,
        'PARAMS_MISSING' => 9983,
        'DUPLICATE_ACTIVITY' => 9984,
        'UNIQUE_NAME_INVALID' => 9985,
        'OPER_OWE_FEE' => 9986,
        'OPER_LOCKED' => 9987,
        'USER_OWE_FEE' => 9988,
        'USER_LOCKED' => 9989,
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