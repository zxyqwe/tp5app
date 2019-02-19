<?php

namespace app\hanbj\controller;

use hanbj\ActivityOper;
use hanbj\BonusOper;
use hanbj\FeeOper;
use hanbj\MemberOper;
use hanbj\weixin\WxTemp;
use think\Controller;
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
        $ts = intval(input('get.ts'));
        if (abs(time() - $ts) > 1800) {
            throw new HttpResponseException(json(['msg' => 'ts误差太大']));
        }
        $sign = $GLOBALS['HTTP_RAW_POST_DATA'] . config('hanbj_rpc_sk') . $ts;
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
        $ret = MemberOper::search_unionid($unionid);
        if (null === $ret) {
            return json(['msg' => "查无此人"]);
        }
        trace("RPC 查询 {$ret['unique_name']} {$ret['code']}");
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
        if (!isset($data['template_id'])
            || !in_array($data["template_id"], WxTemp::temp_ids)
        ) {
            return json(['msg' => 'template_id错误']);
        }

        $unionid = strval($data['touser']);
        $ret = MemberOper::search_unionid($unionid);
        if (null === $ret) {
            return json(['msg' => "查无此人"]);
        }
        if (!is_numeric($ret['code']) || !in_array(intval($ret['code']), MemberOper::getMember())) {
            return json(['msg' => "用户锁住"]);
        }
        if (FeeOper::owe($ret['unique_name'])) {
            return json(['msg' => "用户欠费"]);
        }

        $data['touser'] = $ret['openid'];
        $raw = WxTemp::rpc($data, "RPC 模板 {$ret['unique_name']} " . json_encode($data));
        return json(['msg' => $raw]);
    }

    public function act()
    {
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
        if (!isset($data['act'])
            || !isset($data['bonus'])
            || !isset($data['unionid'])
            || !isset($data['operid'])
        ) {
            return json(['msg' => '缺失参数']);
        }

        $bonus = intval($data['bonus']);
        if ($bonus < 5 || $bonus > 30) {
            return json(['msg' => 'bonus err']);
        }

        $act = strval($data['act']);
        if (strpos($act, date('Y')) !== 0) {
            return json(['msg' => 'act err']);
        }

        $unionid = strval($data['unionid']);
        $ret = MemberOper::search_unionid($unionid);
        if (!is_numeric($ret['code']) || !in_array(intval($ret['code']), MemberOper::getMember())) {
            return json(['msg' => "用户1锁住"]);
        }
        if (FeeOper::owe($ret['unique_name'])) {
            return json(['msg' => "用户1欠费"]);
        }

        $operid = strval($data['operid']);
        $operret = MemberOper::search_unionid($operid);
        if (!is_numeric($operret['code']) || intval($operret['code']) !== MemberOper::NORMAL) {
            return json(['msg' => "用户2锁住"]);
        }
        if (FeeOper::owe($operret['unique_name'])) {
            return json(['msg' => "用户2欠费"]);
        }

        if (null === $ret || null === $operret) {
            return json(['msg' => "查无此人"]);
        }

        trace("RPC 活动 {$operret['unique_name']} -> {$ret['unique_name']}, $act, $bonus");
        ActivityOper::signAct(
            $operret['unique_name'],
            $operret['openid'],
            $act . '志愿者',
            BonusOper::getVolBonus(),
            $operret['unique_name']
        );
        return ActivityOper::signAct(
            $ret['unique_name'],
            $ret['openid'],
            $act,
            $bonus,
            $operret['unique_name']
        );
    }
}