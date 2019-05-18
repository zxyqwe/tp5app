<?php

namespace hanbj;


use think\Db;
use think\exception\HttpResponseException;
use util\MysqlLog;

class UserOper
{
    const VERSION = 'succ_1';
    const WX_VERSION = 'wx_succ_2';
    const time = 60;

    private static function limit($unique)
    {
        return in_array($unique, self::reg());
    }

    private static function toplist()
    {
        $data = array_merge(FameOper::getTop(), HBConfig::FIXED);
        return array_unique($data);
    }

    public static function pretty_toplist()
    {
        return MemberOper::pretty_tieba(MemberOper::get_tieba(self::toplist()));
    }

    public static function grantAllRight($unique)
    {
        return in_array($unique, self::toplist());
    }

    public static function reg()
    {
        // uniq in current fame
        $res = Db::table('fame')
            ->where([
                'year' => HBConfig::YEAR,
                'grade' => ['in', [
                    FameOper::chairman,
                    FameOper::vice_chairman,
                    FameOper::manager,
                    FameOper::vice_manager,
                    FameOper::commissioner,
                    FameOper::secretary,
                    FameOper::vice_secretary,
                    FameOper::like_manager
                ]]
            ])
            ->field(['unique_name as u'])
            ->cache(600)
            ->select();
        $data = [HBConfig::CODER];
        foreach ($res as $item) {
            $data[] = $item['u'];
        }
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
        trace("$unique 登录微信", MysqlLog::LOG);
    }

    public static function nonce($nonce)
    {
        $unique = session('unique_name');
        if (!self::limit($unique)) {
            return;
        }
        $data = ['login' => self::VERSION, 'uni' => $unique];
        cache("login$nonce", json_encode($data), self::time * 2);
        trace("$unique 登录网页", MysqlLog::INFO);
    }

    public static function valid_pc($json)
    {
        $unique = session('unique_name');
        if (!self::limit($unique)) {
            if (strlen($unique) > 0) {
                trace("$unique 尝试登录，拒绝", MysqlLog::ERROR);
            }
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

    public static function wx_login()
    {
        if (self::WX_VERSION !== session('wx_login')) {
            session(null);
        } else {
            self::wx_trace();
            return true;
        }
        if (input('?get.code')) {
            $api = config('hanbj_api');
            $sec = config('hanbj_secret');
            $openid = WX_code(input('get.code'), $api, $sec);
            if (is_string($openid)) {
                session('openid', $openid);
                session('unique_name', $openid);
                session('wx_login', self::WX_VERSION);
                return true;
            } else {
                trace("wx_login " . json_encode($openid), MysqlLog::ERROR);
            }
        }
        return false;
    }

    private static function wx_trace()
    {
        $controller = strtolower(request()->controller());
        $action = strtolower(request()->action());
        if (!($controller === 'mobile'
            && $action === 'index')
        ) {
            $uniq = session('unique_name');
            $tieba = session('tieba_id');
            trace("微信访问 $uniq $tieba $controller $action", MysqlLog::LOG);
        }
    }
}
