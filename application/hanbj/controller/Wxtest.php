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
        $uname = session('unique_name');
        if (!in_array($obj, WxOrg::obj)) {
            return json(['msg' => '参数错误'], 400);
        }
        $data['uname'] = $obj;
        $data['name'] = WxOrg::name;
        $c_name = $uname . $obj . WxOrg::name;
        if (!cache('?' . $c_name)) {
            $data['ans'] = [];
        } else {
            $data['ans'] = json_decode(cache($c_name), true);
        }
        return view('home', ['obj' => json_encode($data)]);
    }

    public function up()
    {
        $obj = input('post.obj');
        if (!in_array($obj, WxOrg::obj)) {
            return json(['msg' => '参数错误'], 400);
        }
        $ans = input('post.ans/a', []);
        if (empty($ans)) {
            return json(['msg' => 'empty ans'], 400);
        } elseif (count($ans) !== 11) {
            return json(['msg' => 'lens ans'], 400);
        }
        $uname = session('unique_name');
        $c_name = $uname . $obj . WxOrg::name;
        cache($c_name, json_encode($ans));
        return json(['msg' => 'OK']);
    }
}