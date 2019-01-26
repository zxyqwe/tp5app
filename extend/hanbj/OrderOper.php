<?php

namespace hanbj;

use think\Db;
use Exception;
use hanbj\weixin\WxTemp;

class OrderOper
{
    const FEE = 1;
    const ACT = 2;
    const FEE_YEAR = [
        ['label' => '续费一年-原价', 'value' => 0, 'fee' => 30],
        ['label' => '续费二年-83折', 'value' => 1, 'fee' => 50],
        ['label' => '续费三年-66折', 'value' => 2, 'fee' => 60]
    ];

    public static function dropfee($outid, $year)
    {
        $openid = session('openid');
        $map['openid'] = $openid;
        $map['type'] = OrderOper::FEE;
        $map['value'] = $year;
        $map['trans'] = '';
        $map['outid'] = $outid;
        $d['label'] = '作废';
        Db::table('order')
            ->where($map)
            ->update($d);
    }

    /**
     *
     * @param \wxsdk\pay\WxPayUnifiedOrder $input
     * @param int $year
     * @return bool|\wxsdk\pay\WxPayUnifiedOrder
     */
    public static function fee($input, $year)
    {
        $fee = OrderOper::FEE_YEAR[$year]['fee'] * 100;
        if (session('unique_name') === HBConfig::CODER) {
            $fee = 1;
        }
        $label = OrderOper::FEE_YEAR[$year]['label'];
        $openid = session('openid');
        $input->SetBody("会员缴费");
        $input->SetDetail('会员缴费：' . $label);
        $input->SetTotal_fee('' . $fee);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $map['openid'] = $openid;
        $map['fee'] = $fee;
        $map['type'] = OrderOper::FEE;
        $map['value'] = $year;
        $map['label'] = $label;
        $map['trans'] = '';
        $res = Db::table('order')
            ->where($map)
            ->field([
                'outid'
            ])
            ->find();
        if (null === $res) {
            $outid = session('card') . date("YmdHis");
            $map['outid'] = $outid;
            $res = Db::table('order')
                ->insert($map);
            if (1 != $res) {
                return false;
            }
            $input->SetOut_trade_no($outid);
        } else {
            $input->SetOut_trade_no($res['outid']);
        }
        return $input;
    }

    private static function handleFee($value, $uname, $trans, $d)
    {
        $value = intval($value) + 1;
        $ins = [];
        $oper = 'Weixin_' . substr($trans, strlen($trans) - 6);
        while (count($ins) < $value) {
            $ins[] = [
                'unique_name' => $uname,
                'oper' => $oper,
                'code' => 1,
                'fee_time' => $d,
                'bonus' => BonusOper::getFeeBonus()
            ];
        }
        $up = Db::table('nfee')
            ->insertAll($ins);
        FeeOper::uncache($uname);
        if ($up != $value) {
            throw new Exception('nfee ' . $value);
        }
    }

    public static function handle($data)
    {
        $outid = $data["out_trade_no"];
        $total_fee = $data['total_fee'];
        $transaction_id = $data['transaction_id'];
        $d = date("Y-m-d H:i:s");
        $map['outid'] = $outid;
        $map['fee'] = $total_fee;
        $ins['trans'] = $transaction_id;
        $ins['time'] = $d;
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        Db::startTrans();
        try {
            $res = Db::table('order')
                ->alias('f')
                ->where($map)
                ->join($join)
                ->field([
                    'f.type',
                    'f.value',
                    'f.label',
                    'm.unique_name',
                    'm.openid'
                ])
                ->find();
            if (null === $res) {
                throw new Exception(json_encode($map));
            }
            $map['trans'] = '';
            $up = Db::table('order')
                ->where($map)
                ->data($ins)
                ->update();
            if ($up === 0) {
                Db::rollback();
                trace('重来订单 ' . json_encode($data));
                return true;
            }
            if (strlen($res['unique_name']) <= 1) {
                $res['type'] = -1;
            }
            switch ($res['type']) {
                case 1:
                    self::handleFee($res['value'], $res['unique_name'], $transaction_id, $d);
                    Db::commit();
                    WxTemp::notifyFee($res['openid'],
                        $res['unique_name'],
                        intval($total_fee) / 100,
                        FeeOper::cache_fee($res['unique_name']),
                        $res['label']);
                    break;
                default:
                    throw new Exception('无名氏 ' . json_encode($map) . ' ' . json_encode($res) . ' ' . json_encode($ins));
            }
        } catch (\Exception $e) {
            Db::rollback();
            $e = $e->getMessage();
            trace('NotifyProcess ' . $e . json_encode($data));
            return false;
        }
        return true;
    }
}
