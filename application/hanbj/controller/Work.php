<?php

namespace app\hanbj\controller;


use app\hanbj\FeeOper;
use think\Db;

class Work
{
    public function json_card()
    {
        if (!in_array(session('unique_name'), config('hanbj_worker'))) {
            return json(['msg' => '非工作人员'], 400);
        }
        $code = input('post.code', 0, FILTER_VALIDATE_INT);
        $map['code'] = $code;
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $res = Db::table('card')
            ->alias('f')
            ->where($map)
            ->field([
                'f.unique_name as uni',
                'm.tieba_id as tie'
            ])
            ->find();
        if (null === $res) {
            return json(['msg' => '查无此人'], 400);
        }
        $res['fee'] = FeeOper::cache_fee($res['uni']);
        return json($res);
    }
}
