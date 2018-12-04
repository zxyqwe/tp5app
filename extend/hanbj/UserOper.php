<?php

namespace hanbj;


use think\exception\HttpResponseException;

class UserOper
{
    const VERSION = 'succ_1';
    const coder = '坎丙午';
    const FIXED = [self::coder, '坤丁酉', '离丙申', '乾壬申'];
    const time = 60;

    private static function limit($unique)
    {
        return in_array($unique, self::reg());
    }

    public static function reg()
    {
        $data = array_merge(FameOper::getTop(), self::FIXED);
        return array_unique($data);
    }

    public static function login()
    {
        $unique = session('unique_name');
        if (!self::limit($unique)) {
            session('login', null);
            return;
        }
        if (self::VERSION === session('login')) {
            return;
        }
        session('login', self::VERSION);
        session('name', $unique);
        trace("$unique 登录微信");
    }

    public static function nonce($nonce)
    {
        $unique = session('unique_name');
        if (!self::limit($unique)) {
            return;
        }
        $data = ['login' => self::VERSION, 'uni' => $unique];
        cache("login$nonce", json_encode($data), self::time * 2);
        trace("$unique 登录网页");
    }

    public static function valid_pc($json = false)
    {
        $unique = session('unique_name');
        if (!self::limit($unique)) {
            session('login', null);
        }
        if (self::VERSION !== session('login')) {
            if ($json) {
                $res = json(['msg' => '未登录'], 400);
            } else {
                $res = redirect('https://app.zxyqwe.com/hanbj/pub/bulletin');
            }
            throw new HttpResponseException($res);
        }
    }
}
