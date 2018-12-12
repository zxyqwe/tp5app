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
        if (!MemberOper::wx_login()) {
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
        $obj = explode('-', $obj['val']);
        $catg = intval($obj[1]);
        $obj = $obj[0];
        $org = new WxOrg($catg);
        if (!in_array($obj, $org->obj)) {
            return json(['msg' => '投票目标错误'], 400);
        }
        $uname = session('unique_name');
        if (!in_array($uname, $org->getUser())) {
            return json(['msg' => '没有投票权'], 400);
        }
        $data['uname'] = $obj;
        $data['name'] = $org->name;
        $data['test'] = $org->test;
        $data['catg'] = $catg;
        $ans = Db::table('score')
            ->where([
                'unique_name' => $uname,
                'name' => $obj,
                'year' => WxOrg::year,
                'catg' => $catg
            ])
            ->field('ans')
            ->find();
        if (null === $ans) {
            $data['ans'] = [];
        } else {
            $data['ans'] = json_decode($ans['ans'], true);
            if (count($data['ans']['sel']) != count($data['test'])) {
                $data['ans'] = [];
            }
        }
        return view('home', ['obj' => json_encode($data)]);
    }

    public function up()
    {
        $obj = input('post.obj');
        $catg = intval(input('post.catg'));
        $org = new WxOrg($catg);
        if (!in_array($obj, $org->obj)) {
            return json(['msg' => '投票目标错误'], 400);
        }
        $ans = input('post.ans/a', []);
        $uname = session('unique_name');
        trace("问卷 $uname " . json_encode($ans));
        $org->checkAns($ans);
        $ans = json_encode($ans);
        if (!in_array($uname, $org->getUser())) {
            return json(['msg' => '没有投票权'], 400);
        }
        return WxOrg::addAns($uname, $obj, $catg, $ans);
    }
}