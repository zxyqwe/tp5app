<?php

namespace hanbj;

use think\Db;
use util\MysqlLog;

class SubscribeOper
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
            $ret = Db::table('idmap')
                ->insert(['openid' => $openid]);
            if ($ret > 0) {
                trace("addOpenID $ret $openid", MysqlLog::LOG);
            }
        }
    }

    public static function maySubscribe($openid)
    {
        $ret = Db::table('idmap')
            ->where(['openid' => $openid])
            ->update(['status' => self::Subscribe]);
        if ($ret > 0) {
            trace("maySubscribe $ret $openid", MysqlLog::LOG);
        }
        cache("addUnionID$openid", null);
    }

    public static function mayUnsubscribe($openid)
    {
        $ret = Db::table('idmap')
            ->where(['openid' => $openid])
            ->update([
                'status' => self::Unsubscribe,
                'unionid' => null
            ]);
        if ($ret > 0) {
            trace("mayUnsubscribe $ret $openid", MysqlLog::LOG);
        }
    }
}
