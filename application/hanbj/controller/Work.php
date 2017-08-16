<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'hanbj/custom.php';
use app\hanbj\BonusOper;
use app\hanbj\FeeOper;
use think\Db;

class Work
{
    public function _empty($name)
    {
        return '';
    }

    public function json_card()
    {
        if (!in_array(session('unique_name'), BonusOper::WORKER)) {
            return json(['msg' => '非工作人员'], 400);
        }
        $code = input('post.code');
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
        $res['act'] = BonusOper::ACT_NAME;
        return json($res);
    }

    public function json_act()
    {
        if (!in_array(session('unique_name'), BonusOper::WORKER)) {
            return json(['msg' => '非工作人员'], 400);
        }
        $code = input('post.code');
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
                'm.unique_name'
            ])
            ->find();
        if (null === $res) {
            return json(['msg' => '查无此人'], 400);
        }
        $data['unique_name'] = $res['unique_name'];
        $data['oper'] = session('unique_name');
        $data['act_time'] = date("Y-m-d H:i:s");
        $data['name'] = BonusOper::ACT_NAME;
        $data['bonus'] = BonusOper::ACT;
        try {
            Db::table('activity')
                ->data($data)
                ->insert();
            return json(['msg' => 'ok']);
        } catch (\Exception $e) {
            $e = '' . $e;
            if (false != strpos($e, 'constraint')) {
                return json(['msg' => 'ok']);
            }
            return json(['msg' => $e], 400);
        }
    }
}
