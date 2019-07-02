<?php

namespace hanbj;

use think\Db;
use util\MysqlLog;

class SubscibeOper
{
    const Unknown = 0;
    const Subscribe = 1;
    const Unsubscribe = 2;

    public static function getNoUnionId()
    {
        return Db::table('idmap')
            ->where([
                'unionid' => ['exp', Db::raw('is null')]
            ])
            ->field(['openid'])
            ->select();
    }

    public static function setUnionidOnOpenid($openid, $unionid)
    {
        $ret = Db::table('idmap')
            ->where([
                'openid' => $openid,
                'unionid' => ['exp', Db::raw('is null')]
            ])
            ->data([
                'unionid' => $unionid,
                'status' => self::Subscribe
            ])
            ->update();
        if ($ret > 0) {
            trace("addUnionID $ret $openid -- $unionid", MysqlLog::INFO);
        }
        return $ret;
    }

    public static function mayAddNewOpenid($openid)
    {
        $idmap = Db::table('idmap')
            ->where(['openid' => $openid])
            ->find();
        if (null === $idmap) {
            Db::table('idmap')
                ->insert(['openid' => $openid]);
        }
    }

    public static function maySubscribe($openid)
    {
        Db::table('idmap')
            ->where(['openid' => $openid])
            ->update(['status' => self::Subscribe]);
        cache("addUnionID$openid", null);
    }

    public static function mayUnsubscribe($openid)
    {
        Db::table('idmap')
            ->where(['openid' => $openid])
            ->update([
                'status' => self::Unsubscribe,
                'unionid' => null
            ]);
    }
}
