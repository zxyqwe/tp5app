<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'hanbj/custom.php';
include_once APP_PATH . 'hanbj/WxConfig.php';
include_once APP_PATH . 'WxPay.php';
use app\hanbj\BonusOper;
use app\hanbj\OrderOper;
use app\HanbjRes;
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
        if (cache('?json_renew' . $uname)) {
            return json(['msg' => '每十分钟可以重新核算一次'], 400);
        }
        cache('json_renew' . $uname, 1, 600);
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

    public function fee_year()
    {
        return json(OrderOper::FEE_YEAR);
    }

    public function order()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        if (!session('?card')) {
            return json(['msg' => '没有会员卡'], 400);
        }
        $opt = input('post.opt', 0, FILTER_VALIDATE_INT);
        $type = input('post.type', 1, FILTER_VALIDATE_INT);
        $input = new WxPayUnifiedOrder();
        if ($type === 1) {
            if ($opt < 0 || $opt >= count(OrderOper::FEE_YEAR)) {
                return json(['msg' => '年数错误'], 400);
            }
            $input = OrderOper::fee($input, $opt);
            if (false === $input) {
                return json(['msg' => '下单失败'], 400);
            }
        } else {
            return json(['msg' => '参数错误'], 400);
        }
        $order = WxPayApi::unifiedOrder($input);
        if (!array_key_exists('prepay_id', $order)) {
            $msg = $order['return_msg'] . $input->ToXml();
            trace($msg);
            return json(['msg' => $msg], 400);
        }
        $data['appId'] = \WxPayConfig::APPID;
        $data['timeStamp'] = time();
        $data['nonceStr'] = getNonceStr();
        $data['package'] = 'prepay_id=' . $order['prepay_id'];
        $data['signType'] = 'MD5';
        $res = new HanbjRes();
        $data['paySign'] = $res->setValues($data);
        $data['timestamp'] = $data['timeStamp'];
        return json($data);
    }

    public function notify()
    {
        $hand = new HanbjNotify();
        $hand->Handle(false);
    }
}