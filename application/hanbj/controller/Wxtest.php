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
        $org = new WxOrg(1);
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
        $obj = explode('-', $obj['val']);
        $catg = $obj[1];
        $obj = $obj[0];
        $uname = session('unique_name');
        $org = new WxOrg(intval($catg));
        if (!in_array($obj, $org->obj)) {
            return json(['msg' => '参数错误'], 400);
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
        $catg = input('post.catg');
        $org = new WxOrg(intval($catg));
        if (!in_array($obj, $org->obj)) {
            return json(['msg' => '参数错误'], 400);
        }
        $ans = input('post.ans/a', []);
        $org->checkAns($ans);
        $ans = json_encode($ans);
        $uname = session('unique_name');
        try {
            $ret = Db::table('score')
                ->where([
                    'unique_name' => $uname,
                    'name' => $obj,
                    'year' => WxOrg::year,
                    'catg' => $catg
                ])
                ->data(['ans' => $ans])
                ->update();
            if ($ret <= 0) {
                Db::table('score')
                    ->data([
                        'ans' => $ans,
                        'unique_name' => $uname,
                        'name' => $obj,
                        'year' => WxOrg::year,
                        'catg' => $catg
                    ])
                    ->insert();
            }
        } catch (\Exception $e) {
            $e = $e->getMessage();
            preg_match('/Duplicate entry \'(.*)-(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                return json(['msg' => 'OK']);
            }
            trace("Test UP $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
        return json(['msg' => 'OK']);
    }
}