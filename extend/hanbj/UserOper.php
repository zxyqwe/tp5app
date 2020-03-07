<?php

namespace hanbj;


use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use util\MysqlLog;

class UserOper
{
    const VERSION = 'succ_1';
    const WX_VERSION = 'wx_succ_2';
    const time = 60;

    /**
     * @param $unique
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function limit($unique)
    {
        return in_array($unique, self::reg());
    }

    public static function toplist()
    {
        $data = array_merge(FameOper::getTop(), HBConfig::FIXED);
        return array_unique($data);
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function pretty_toplist()
    {
        return MemberOper::pretty_tieba(MemberOper::get_tieba(self::toplist()));
    }

    public static function grantAllRight($unique)
    {
        return in_array($unique, self::toplist());
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public static function reg()
    {
        $res = FameOper::getWhoCanLogIn();
        $data = HBConfig::FIXED;
        foreach ($res as $item) {
            $data[] = $item['u'];
        }
        return array_unique($data);
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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

    /**
     * @param $nonce
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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

    /**
     * @param $json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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
                $res = redirect('https://app.zxyqwe.com/hanbj/pub/bonus');
            }
            throw new HttpResponseException($res);
        }
    }

    public static function wx_login()
    {
        self::try_fake_wx_id_for_developer();
        if (self::WX_VERSION !== session('wx_login')) {
            session(null);
        } else {
            self::wx_trace();
            return true;
        }
        if (input('?get.code')) {
            $api = config('hanbj_api');
            $sec = config('hanbj_secret');
            $code_auth = WX_code(input('get.code'), $api, $sec);
            if ($code_auth) {
                session('unique_name', session('openid'));
                session('wx_login', self::WX_VERSION);
                return true;
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

    private static function try_fake_wx_id_for_developer()
    {
        if (session('unique_name') !== HBConfig::CODER) {
            return;
        }
        if (!cache("?set_fake_wx_id")) {
            return;
        }
        $openid = cache("set_fake_wx_id");
        session('openid', $openid);
        session('unique_name', $openid);
    }

    public static function set_fake_wx_id($unique)
    {
        $ret = Db::table('member')
            ->where([
                'unique_name' => $unique
            ])
            ->value('openid');
        if (null !== $ret) {
            $ret = strval($ret);
            cache("set_fake_wx_id", $ret, 600);
        }
        return $ret;
    }
}
