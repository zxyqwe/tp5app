<?php

namespace app\hanbj\controller;

use app\hanbj\WxOrg;
use think\Controller;
use think\exception\HttpResponseException;

class Wxtest extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        $uname = session('unique_name');
        if (!in_array($uname, WxOrg::getAll())) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return '';
    }

    public function index($obj = '')
    {
        if (!WX_iter(config('hanbj_api'), config('hanbj_secret'))) {
            return WX_redirect('https://app.zxyqwe.com/hanbj/wxtest/obj/' . $obj, config('hanbj_api'));
        }
        if (input('?get.encrypt_code')) {
            return redirect('https://app.zxyqwe.com/hanbj/wxtest/obj/' . $obj);
        }
        $uname = session('unique_name');
        $obj = htmlspecialchars_decode($obj);
        if (!in_array($obj, WxOrg::obj)) {
            return json(['msg' => '参数错误'], 400);
        }
        $data['uname'] = $obj;
        $data['name'] = WxOrg::name;
        $data['test'] = WxOrg::test;
        $data['ans'] = cache($uname . $obj . WxOrg::name);
        return view('home', ['obj' => json_encode($data)]);
    }
}