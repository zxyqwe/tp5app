<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'hanbj/custom.php';
include_once APP_PATH . 'hanbj/WxConfig.php';
include_once APP_PATH . 'WxPay.php';
use app\hanbj\BonusOper;
use think\Db;
use app\HanbjNotify;
use app\hanbj\FeeOper;
use app\hanbj\CardOper;
use app\WxPayUnifiedOrder;
use app\WxPayApi;

class Wx
{
    public function json_activity()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = 5;
        $offset = max(0, $offset);
        $uname = session('unique_name');
        $map['unique_name'] = $uname;
        $card = Db::table('activity')
            ->where($map)
            ->limit($offset, $size)
            ->order('act_time', 'desc')
            ->field([
                'oper',
                'name',
                'act_time'
            ])
            ->select();
        return json(['list' => $card, 'size' => $size]);
    }

    public function json_valid()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = 5;
        $offset = max(0, $offset);
        $uname = session('unique_name');
        $map['unique_name'] = $uname;
        $card = Db::table('nfee')
            ->where($map)
            ->limit($offset, $size)
            ->order('fee_time', 'desc')
            ->field([
                'oper',
                'code',
                'fee_time'
            ])
            ->select();
        return json(['list' => $card, 'size' => $size, 'real_year' => FeeOper::cache_fee($uname)]);
    }

    public function json_renew()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $uname = session('unique_name');
        $bonus = BonusOper::reCalc($uname);
        $map['unique_name'] = $uname;
        $res = Db::table('member')
            ->where($map)
            ->setField('bonus', $bonus);
        if ($res !== 1) {
            return json(['msg' => '更新失败，积分为' . $bonus], 400);
        }
        $cardup = CardOper::update(
            $uname,
            session('card'),
            $bonus,
            $bonus,
            '重新计算积分');
        if ($cardup !== true) {
            return $cardup;
        }
        return json(['msg' => $bonus]);
    }

    public function order()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        if (!session('?card')) {
            return json(['msg' => '没有会员卡'], 400);
        }
        $openid = session('openid');
        $input = new WxPayUnifiedOrder();
        $input->SetBody("设置商品简要描述");
        $input->SetDetail('设置商品名称明细列表');
        $input->SetOut_trade_no(session('card') . date("YmdHis"));
        $input->SetTotal_fee("1");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $order = WxPayApi::unifiedOrder($input);
        return json();
    }

    public function notify()
    {
        $hand = new HanbjNotify();
        $hand->Handle(false);
    }
}