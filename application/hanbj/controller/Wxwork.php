<?php

namespace app\hanbj\controller;

use hanbj\ActivityOper;
use hanbj\BonusOper;
use hanbj\FeeOper;
use hanbj\MemberOper;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;

class Wxwork extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if (!MemberOper::wx_login() || !in_array(session('unique_name'), BonusOper::getWorkers())) {
            $res = json(['msg' => '非工作人员'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return json([], 404);
    }

    public function json_card()
    {
        $code = input('post.code');
        if (!is_numeric($code)) {
            $code = 0;
        }
        $map['f.code'] = $code;
        $map['m.code'] = ['in', MemberOper::getMember()];
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $res = Db::table('card')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->field([
                'm.unique_name as uni',
                'm.tieba_id as tie',
                'm.year_time as yt'
            ])
            ->find();
        if (null === $res) {
            return json(['msg' => '查无此人：' . $code], 400);
        }
        $res['fee'] = FeeOper::cache_fee($res['uni']);
        $res['act'] = BonusOper::getActName();
        return json($res);
    }

    public function json_act()
    {
        $code = input('post.code');
        if (!is_numeric($code)) {
            $code = 0;
        }
        $map['f.code'] = $code;
        $map['m.code'] = ['in', MemberOper::getMember()];
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $res = Db::table('card')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->field([
                'm.unique_name',
                'm.openid'
            ])
            ->find();
        if (null === $res) {
            return json(['msg' => '查无此人：' . $code], 400);
        }
        return ActivityOper::signAct($res['unique_name'], $res['openid'], BonusOper::getActName(), BonusOper::getActBonus());
    }

    public function act_log()
    {
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $own = input('post.own', false, FILTER_VALIDATE_BOOLEAN);
        $size = 5;
        $offset = max(0, $offset);
        $act = BonusOper::getActName();
        $map['name'] = $act;
        if ($own) {
            $map['f.oper'] = session('unique_name');
        }
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $card = Db::table('activity')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->limit($offset, $size)
            ->order('act_time', 'desc')
            ->field([
                'f.oper as o',
                'f.unique_name as u',
                'f.act_time as ot',
                'm.tieba_id as t'
            ])
            ->select();
        return json(['list' => $card, 'name' => $act, 'size' => $size]);
    }
}
