<?php

namespace app\hanbj\controller;

use hanbj\BonusOper;
use hanbj\MemberOper;
use hanbj\OrderOper;
use hanbj\weixin\HanbjRes;
use hanbj\vote\WxOrg;
use think\Controller;
use think\Db;
use hanbj\weixin\HanbjNotify;
use hanbj\FeeOper;
use app\hanbj\WxPayConfig;
use app\WxPayUnifiedOrder;
use app\WxPayApi;
use think\exception\HttpResponseException;

class Wxclub extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if (!MemberOper::wx_login()) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return json([], 404);
    }

    public function index()
    {
        return view('home', ['obj' => json_encode([])]);
    }
}