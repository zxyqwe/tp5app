<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'hanbj/custom.php';
use app\hanbj\FeeOper;
use think\Db;

class Work
{
    public function json_card()
    {
        if (!in_array(session('unique_name'), config('hanbj_worker'))) {
            return json(['msg' => '非工作人员'], 400);
        }
        $code = input('post.code');
        $code = 416521837905;//ToDo
        if (!is_numeric($code)) {
            $code = 0;
        }
        $map['f.code'] = $code;
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $res = Db::table('card')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->field([
                'm.unique_name as uni',
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
