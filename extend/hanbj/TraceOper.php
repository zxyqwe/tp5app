<?php

namespace hanbj;


use PDOStatement;
use think\Collection;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class TraceOper
{
    /**
     * @param $unique_name
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function trace($unique_name)
    {
        $openid = null;
        $ret = Db::table('member')
            ->where(['unique_name' => $unique_name])
            ->field(['openid'])
            ->find();
        if (null !== $ret) {
            $openid = $ret['openid'];
        }
        return [
            'act' => self::trace_act($unique_name),
            'fame' => self::trace_fame($unique_name),
            'wx' => self::trace_wx($openid),
            "log" => self::trace_log($unique_name, $openid),
            "member" => self::trace_member($unique_name, $openid),
            "fee" => self::trace_fee($unique_name),
            "order" => self::trace_order($openid),
            "payout" => self::trace_payout($openid)
        ];
    }

    /**
     * @param $unique_name
     * @return false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private static function trace_act($unique_name)
    {
        return Db::table("activity")
            ->where(['unique_name' => ['like', "%$unique_name%"]])
            ->order("id")
            ->select();
    }

    /**
     * @param $unique_name
     * @return false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private static function trace_fame($unique_name)
    {
        return Db::table("fame")
            ->where(['unique_name' => ['like', "%$unique_name%"]])
            ->order("id")
            ->select();
    }

    private static function trace_wx($openid)
    {
        if (is_null($openid)) {
            return "";
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=' . WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $data = ['user_list' => ["openid" => $openid]];
        return Curl_Post($data, $url, false);
    }

    /**
     * @param $unique_name
     * @param $openid
     * @return false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function trace_log($unique_name, $openid)
    {
        $ret = Db::table('logs')
            ->where("query|msg", "like", "%$unique_name%");
        if (!is_null($openid)) {
            $ret = $ret->whereOr("query|msg", "like", "%$openid%");
        }
        return $ret->order("id")->select();
    }

    /**
     * @param $unique_name
     * @param $openid
     * @return false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function trace_member($unique_name, $openid)
    {
        $ret = Db::table('member')
            ->where("unique_name", "like", "%$unique_name%");
        if (!is_null($openid)) {
            $ret = $ret->whereOr("openid", "like", "%$openid%");
        }
        return $ret->order("id")->select();
    }

    /**
     * @param $unique_name
     * @return false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private static function trace_fee($unique_name)
    {
        return Db::table("nfee")
            ->where(['unique_name' => ['like', "%$unique_name%"]])
            ->order("id")
            ->select();
    }

    /**
     * @param $openid
     * @return array|false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private static function trace_order($openid)
    {
        if (is_null($openid)) {
            return [];
        }
        return Db::table("order")
            ->where(['openid' => "$openid"])
            ->order("id")
            ->select();
    }

    /**
     * @param $openid
     * @return array|false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private static function trace_payout($openid)
    {
        if (is_null($openid)) {
            return [];
        }
        return Db::table("payout")
            ->where(['openid' => "$openid"])
            ->order("id")
            ->select();
    }
}