<?php

namespace app\hanbj\controller;

use hanbj\MemberOper;
use hanbj\vote\WxOrg;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;

class Wxtest extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        $uname = session('unique_name');
        $org = new WxOrg();
        if (!MemberOper::wx_login() || !in_array($uname, $org->getUser())) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        abort(404, '页面不存在', []);
    }

    public function index($obj = '')
    {
        $obj = cache('jump' . $obj);
        $obj = json_decode($obj, true);
        $obj = $obj['val'];
        $uname = session('unique_name');
        $org = new WxOrg();
        if (!in_array($obj, $org->obj)) {
            return json(['msg' => '参数错误'], 400);
        }
        $map['unique_name'] = $obj;
        $ret = Db::table('member')
            ->where($map)
            ->field([
                'tieba_id as u'
            ])
            ->find();
        $data['uname'] = "$obj - {$ret['u']}";
        $data['name'] = WxOrg::name;
        $data['test'] = WxOrg::test;
        $ans = Db::table('score')
            ->where([
                'unique_name' => $uname,
                'name' => $obj,
                'year' => WxOrg::year
            ])
            ->field('ans')
            ->find();
        if (null === $ans) {
            $data['ans'] = [];
        } else {
            $data['ans'] = json_decode($ans, true);
        }
        return view('home', ['obj' => json_encode($data)]);
    }

    public function up()
    {
        $obj = input('post.obj');
        $org = new WxOrg();
        if (!in_array($obj, $org->obj)) {
            return json(['msg' => '参数错误'], 400);
        }
        $ans = input('post.ans/a', []);
        WxOrg::checkAns($ans);
        $ans = json_encode($ans);
        $uname = session('unique_name');
        try {
            $ret = Db::table('score')
                ->where([
                    'unique_name' => $uname,
                    'name' => $obj,
                    'year' => WxOrg::year
                ])
                ->data(['ans' => $ans])
                ->update();
            if ($ret <= 0) {
                Db::table('score')
                    ->data([
                        'ans' => $ans,
                        'unique_name' => $uname,
                        'name' => $obj,
                        'year' => WxOrg::year])
                    ->insert();
            }
        } catch (\Exception $e) {
            $e = $e->getMessage();
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
        return json(['msg' => 'OK']);
    }
}