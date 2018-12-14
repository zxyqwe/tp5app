<?php

namespace app\hanbj\controller;

use hanbj\FeeOper;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;

class Rpc extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            throw new HttpResponseException(json(['msg' => '空 POST body']));
        }
        $sign = $GLOBALS['HTTP_RAW_POST_DATA'] . config('hanbj_rpc_sk');
        $sign = md5($sign);
        $post_sign = input('get.sign');
        if ($post_sign !== $sign) {
            throw new HttpResponseException(json(['msg' => "错误校验 $post_sign"]));
        }
    }

    public function _empty()
    {
        throw new HttpResponseException(json(['msg' => '页面不存在']));
    }

    public function user()
    {
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        if (!isset($data['unionid'])) {
            return json(['msg' => '无unionID']);
        }
        $unionid = strval($data['unionid']);
        $ret = Db::table('member')
            ->where(['unionid' => $unionid])
            ->cache(600)
            ->field([
                'unique_name',
                'code'
            ])
            ->find();
        if (null === $ret) {
            return json(['msg' => "查无此人"]);
        }
        trace("查询 {$ret['unique_name']} {$ret['code']}");
        return json([
            'msg' => 'ok',
            'user' => intval($ret['code']),
            'fee' => FeeOper::owe($ret['unique_name'])
        ]);
    }

    public function temp()
    {
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        if (!isset($data['touser'])) {
            return json(['msg' => '无unionID']);
        }
        $unionid = strval($data['touser']);
        $ret = Db::table('member')
            ->where(['unionid' => $unionid])
            ->cache(600)
            ->field([
                'unique_name',
                'openid'
            ])
            ->find();
        if (null === $ret) {
            return json(['msg' => "查无此人"]);
        }
        trace("模板 {$ret['unique_name']}");
        return json(['msg' => 'ok']);
    }
}