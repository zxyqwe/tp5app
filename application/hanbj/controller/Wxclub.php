<?php

namespace app\hanbj\controller;

use hanbj\MemberOper;
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
        $unique_name = session('unique_name');
        $d = date("Y-m-d");
        $join = [
            ['member f', 'm.owner=f.unique_name', 'left']
        ];
        $club = Db::table('club')
            ->alias('m')
            ->join($join)
            ->where('stop_time >= :d AND (m.code = 1 OR owner = :uni)', ['d' => $d, 'uni' => $unique_name])
            ->field([
                'id',
                'name',
                'owner',
                'worker',
                'start_time',
                'stop_time',
                'f.tieba_id as nick',
                'm.code'
            ])
            ->select();
        $map['m.code'] = ['in', MemberOper::getMember()];
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
        return view('home', [
            'obj' => json_encode($club),
            'apply' => json_encode([
                'uni' => $unique_name,
                'worker' => $already
            ])
        ]);
    }
}