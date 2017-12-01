<?php

namespace app\hanbj\controller;

use app\hanbj\BonusOper;
use app\hanbj\MemberOper;
use app\hanbj\OrderOper;
use app\hanbj\HanbjRes;
use think\Controller;
use think\Db;
use app\hanbj\HanbjNotify;
use app\hanbj\FeeOper;
use app\hanbj\CardOper;
use app\WxPayUnifiedOrder;
use app\WxPayApi;
use think\exception\HttpResponseException;

class Wx extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'notify']
    ];

    protected function valid_id()
    {
        if (!session('?openid')) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return '';
    }

    public function json_activity()
    {
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
                'act_time',
                'bonus'
            ])
            ->select();
        return json(['list' => $card, 'size' => $size]);
    }

    public function json_valid()
    {
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
                'fee_time',
                'bonus'
            ])
            ->select();
        return json(['list' => $card, 'size' => $size, 'real_year' => FeeOper::cache_fee($uname)]);
    }

    public function json_renew()
    {
        $uname = session('unique_name');
        if (cache('?json_renew' . $uname)) {
            return json(['msg' => '每十分钟可以重新核算一次'], 400);
        }
        cache('json_renew' . $uname, 1, 600);
        $bonus = BonusOper::reCalc($uname);
        $map['unique_name'] = $uname;
        $map['code'] = MemberOper::NORMAL;
        $res = Db::table('member')
            ->where($map)
            ->setField('bonus', $bonus);
        if ($res !== 1) {
            return json(['msg' => '更新失败，积分为' . $bonus], 400);
        }
        CardOper::update(
            $uname,
            session('card'),
            $bonus,
            $bonus,
            '重新计算积分');
        return json(['msg' => $bonus]);
    }

    public function fee_year()
    {
        return json(OrderOper::FEE_YEAR);
    }

    public function order()
    {
        if (!session('?card')) {
            return json(['msg' => '没有会员卡'], 400);
        }
        $opt = input('post.opt', 0, FILTER_VALIDATE_INT);
        $type = input('post.type', OrderOper::FEE, FILTER_VALIDATE_INT);
        $input = new WxPayUnifiedOrder();
        if ($type === OrderOper::FEE) {
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
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            return '';
        }
        $hand = new HanbjNotify();
        $hand->Handle(false);
        return '';
    }

    public function unused()
    {
        switch ($this->request->method()) {
            case 'GET':
                $ret = MemberOper::list_code(MemberOper::UNUSED);
                $rst = [];
                foreach ($ret as $i) {
                    if (strpos($i, '夏') !== false) {
                        $rst[] = $i;
                    }
                }
                sort($rst);
                return json(['data' => $rst]);
            case 'POST':
                $openid = session('openid');
                $tieba_id = input('post.tie');
                $unique_name = input('post.uni');
                if (empty($tieba_id) || empty($unique_name)) {
                    return json(['msg' => '名称错误'], 400);
                }
                $ret = MemberOper::Unused2Temp($unique_name, $tieba_id, $openid);
                if ($ret) {
                    return json(['msg' => 'ok']);
                }
                return json(['msg' => '会员编号没抢到'], 400);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }
}