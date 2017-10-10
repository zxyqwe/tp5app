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

    public function index()
    {
        $uname = session('unique_name');
        $obj = [];
        foreach (WxOrg::obj as $item) {
            if (!cache($item . WxOrg::name . $uname)) {
                $obj[] = $item;
            }
        }
        $data['uname'] = $obj;
        $data['name'] = WxOrg::name;
        $data['test'] = WxOrg::test;
        return view('home', ['obj' => json_encode($data)]);
    }
}