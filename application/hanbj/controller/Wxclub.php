<?php

namespace app\hanbj\controller;

use hanbj\MemberOper;
use hanbj\weixin\WxHanbj;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;

class Wxclub extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'index']
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
        if (!MemberOper::wx_login()) {
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile', config('hanbj_api'));
        }
        $url = 'https://app.zxyqwe.com' . $_SERVER['REQUEST_URI'];
        session('json_wx', WxHanbj::json_wx($url));
        $unique_name = session('unique_name');
        $d = date("Y-m-d");
        $join = [
            ['member f', 'm.owner=f.unique_name', 'left'],
            ['member n', 'n.unique_name=m.worker', 'left']
        ];
        $club = Db::table('club')
            ->alias('m')
            ->join($join)
            ->where('`stop_time` >= :d AND (`m`.`code` = 1 OR `owner` = :uni)', ['d' => $d, 'uni' => $unique_name])
            ->field([
                'm.id',
                'name',
                'owner',
                'worker',
                'start_time',
                'stop_time',
                'f.tieba_id as nick',
                'n.tieba_id as nick2',
                'm.code'
            ])
            ->select();
        $map['code'] = ['in', MemberOper::getMember()];
        $map['unique_name'] = ['NEQ', $unique_name];
        $mem = Db::table('member')
            ->where($map)
            ->field('unique_name as u')
            ->cache(600)
            ->select();
        $already = [];
        foreach ($mem as $i) {
            $already[] = $i['u'];
        }
        sort($already);
        return view('home', [
            'obj' => json_encode($club),
            'apply' => json_encode([
                'uni' => $unique_name,
                'worker' => $already,
                'year' => date("Y")
            ])
        ]);
    }

    public function add_club()
    {
        $a_name = input('post.a_name');
        $w_name = input('post.w_name', '');
        $a_time = input('post.a_time');
        $e_time = input('post.e_time');
        if (strlen($a_name) < 1 || $a_time > $e_time) {
            return json(['msg' => '参数错误'], 400);
        }
        $a_name = date('Y') . $a_name;
        $unique_name = session('unique_name');
        $data = [
            'name' => $a_name,
            'owner' => $unique_name,
            'worker' => $w_name,
            'start_time' => $a_time,
            'stop_time' => $e_time
        ];
        try {
            Db::table('club')
                ->data($data)
                ->insert();
            trace("Club Apply " . json_encode($data));
            return json('提交成功！');
        } catch (\Exception $e) {
            return json(['msg' => $e->getMessage()], 400);
        }
    }
}