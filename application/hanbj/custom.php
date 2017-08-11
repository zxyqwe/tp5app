<?php

namespace app\hanbj;

use think\Db;

class FeeOper
{
    public static function cache_fee($uname)
    {
        $cache_name = 'cache_fee' . $uname;
        if (cache('?' . $cache_name)) {
            return cache($cache_name);
        }
        $map['unique_name'] = $uname;
        $res = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->field([
                'sum(f.code) as n'
            ])
            ->find();
        $year = Db::table('member')
            ->where($map)
            ->value('year_time');
        $fee = intval($year) + intval($res['n']) - 1;
        cache($cache_name, $fee);
        return $fee;
    }

    public static function uncache($uname)
    {
        cache('cache_fee' . $uname, null);
    }
}

class WxHanbj
{

    public static function jsapi()
    {
        if (cache('?jsapi')) {
            return cache('jsapi');
        }
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $res = Curl_Get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $access . '&type=jsapi');
        $res = json_decode($res, true);
        if ($res['errcode'] !== 0) {
            trace(json_encode($res));
            return '';
        }
        cache('jsapi', $res['ticket'], $res['expires_in'] - 10);
        return $res['ticket'];
    }

    public static function ticketapi()
    {
        if (cache('?ticketapi')) {
            return cache('ticketapi');
        }
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $res = Curl_Get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $access . '&type=wx_card');
        $res = json_decode($res, true);
        if ($res['errcode'] !== 0) {
            trace(json_encode($res));
            return '';
        }
        cache('ticketapi', $res['ticket'], $res['expires_in'] - 10);
        return $res['ticket'];
    }

    public static function handle_msg($msg)
    {
        $msg = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        $type = (string)$msg->MsgType;
        switch ($type) {
            case 'event':
                return self::do_event($msg);
            default:
                trace(json_encode($msg));
            case 'text':
            case 'image':
            case 'voice':
            case 'video':
            case 'shortvideo':
            case 'location':
            case 'link':
                return self::auto((string)$msg->FromUserName, (string)$msg->ToUserName, $type);
        }
    }

    private static function auto($to, $from, $type)
    {
        trace(implode(':', [
            $to,
            $type
        ]));
        $data = '<xml>' .
            '<ToUserName><![CDATA[%s]]></ToUserName>' .
            '<FromUserName><![CDATA[%s]]></FromUserName>' .
            '<CreateTime>%s</CreateTime>' .
            '<MsgType><![CDATA[text]]></MsgType>' .
            '<Content><![CDATA[机器人自动回复：%s]]></Content>' .
            '</xml>';
        return sprintf($data, $to, $from, time(), $type);
    }

    private static function do_event($msg)
    {
        $type = (string)$msg->Event;
        switch ($type) {
            case 'user_del_card':
                return CardOper::del_card($msg);
            case 'user_get_card':
                return CardOper::get_card($msg);
            case 'TEMPLATESENDJOBFINISH':
                $Status = (string)$msg->Status;
                if ('success' != $Status) {
                    trace(json_encode($msg));
                }
                return '';
            case 'update_member_card':
                $UserCardCode = (string)$msg->UserCardCode;
                $ModifyBonus = (string)$msg->ModifyBonus;
                trace($UserCardCode . ' --> ' . $ModifyBonus);
                return '';
            default:
                trace(json_encode($msg));
            case 'subscribe':
            case 'SCAN':
            case 'LOCATION':
            case 'CLICK':
            case 'VIEW':
            case 'user_view_card':
                //case 'card_pass_check':
            case 'user_gifting_card':
                //case 'user_pay_from_pay_cell':
            case 'user_enter_session_from_card':
                //case 'update_member_card':
            case 'card_sku_remind':
                //case 'card_pay_order':
                //case 'submit_membercard_user_info':
                return '';
        }
    }
}

class CardOper
{
    public static function update($uni, $card, $add_b, $b, $msg)
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $access;
        $data = [
            'code' => $card,
            'card_id' => config('hanbj_cardid'),
            'background_pic_url' => config('hanbj_img1'),
            'record_bonus' => $msg,
            'bonus' => $b,
            'add_bonus' => $add_b,
            'custom_field_value2' => FeeOper::cache_fee($uni),
            "notify_optional" => [
                "is_notify_bonus" => true,
                "is_notify_custom_field2" => true
            ]
        ];
        $res = Curl_Post($data, $url, false);
        $res = json_decode($res, true);
        if ($res['errcode'] !== 0) {
            trace(json_encode($res));
            return json(['msg' => json_encode($res)], 400);
        }
        return true;
    }

    public static function active($code)
    {
        $uname = session('unique_name');
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/membercard/activate?access_token=' . $access;
        $data = [
            "membership_number" => $code,
            "code" => $code,
            "card_id" => config('hanbj_cardid'),
            'init_bonus' => BonusOper::reCalc($uname),
            'init_custom_field_value1' => $uname,
            'init_custom_field_value2' => FeeOper::cache_fee($uname)
        ];
        $res = Curl_Post($data, $url, false);
        $res = json_decode($res, true);
        if ($res['errcode'] !== 0) {
            trace(json_encode($res));
            return json(['msg' => json_encode($res)], 400);
        }
        $map['status'] = 0;
        $map['code'] = $code;
        $map['openid'] = session('openid');
        $res = Db::table('card')
            ->where($map)
            ->setField('status', 1);
        if ($res !== 1) {
            trace($data);
            return json(['msg' => '更新失败'], 500);
        }
        return json(['msg' => 'OK']);
    }

    public static function del_card($msg)
    {
        $cardid = (string)$msg->UserCardCode;
        $openid = (string)$msg->FromUserName;
        $data = [
            'openid' => $openid,
            'code' => $cardid
        ];
        $res = Db::table('card')
            ->where($data)
            ->delete();
        if ($res !== 1) {
            $data['status'] = 'del fail';
        } else {
            $data['status'] = 'del OK';
        }
        trace(json_encode($data));
        return '';
    }

    public static function get_card($msg)
    {
        $cardid = (string)$msg->UserCardCode;
        $openid = (string)$msg->FromUserName;
        $data = [
            'openid' => $openid,
            'code' => $cardid
        ];
        $res = Db::table('card')
            ->insert($data);
        if ($res !== 1) {
            trace($msg);
        }
        return '';
    }
}

class BonusOper
{
    const FEE = 30;
    const ACT = 30;
    const ACT_NAME = '2017七夕';
    const WORKER = ['坎丙午'];

    public static function reCalc($uname)
    {
        $map['up'] = 1;
        $map['unique_name'] = $uname;
        $act = Db::table('activity')
            ->where($map)
            ->sum('bonus');
        $res = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->sum('f.bonus');
        return intval($act) + intval($res);
    }

    public static function upFee()
    {
        $map['up'] = 0;
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left'],
            ['card c', 'c.openid=m.openid', 'left']
        ];
        $item = Db::table('nfee')
            ->alias('f')
            ->order('f.id')
            ->where($map)
            ->join($join)
            ->field([
                'f.id',
                'f.code as c',
                'm.unique_name',
                'm.openid',
                'm.bonus',
                'c.code',
                'f.bonus as b'
            ])
            ->find();
        if (null != $item) {
            $bonus = intval($item['b']);
            $map['id'] = $item['id'];
            Db::startTrans();
            try {
                $nfee = Db::table('nfee')
                    ->where($map)
                    ->update(['up' => 1]);
                if ($nfee !== 1) {
                    throw new \Exception('更新事件失败' . json_encode($map));
                }
                $nfee = Db::table('member')
                    ->where(['unique_name' => $item['unique_name']])
                    ->setField('bonus', ['exp', 'bonus+(' . $bonus . ')']);
                if ($nfee !== 1) {
                    throw new \Exception('更新积分失败' . json_encode($item));
                }
                Db::commit();
                if ($item['code'] !== null) {
                    $cardup = CardOper::update(
                        $item['unique_name'],
                        $item['code'],
                        $bonus,
                        intval($item['bonus']) + $bonus,
                        '会费记录变更');
                    if ($cardup !== true) {
                        return $cardup;
                    }
                }
            } catch (\Exception $e) {
                Db::rollback();
                return json(['msg' => '' . $e], 400);
            }
        }
        return json(['msg' => 'ok', 'c' => count($item)]);
    }

    public static function upAct()
    {
        $map['up'] = 0;
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left'],
            ['card c', 'c.openid=m.openid', 'left']
        ];
        $item = Db::table('activity')
            ->alias('f')
            ->order('f.id')
            ->where($map)
            ->join($join)
            ->field([
                'f.id',
                'm.unique_name',
                'm.openid',
                'm.bonus',
                'c.code',
                'f.bonus as b'
            ])
            ->find();
        if (null!=$item) {
            $bonus = intval($item['b']);
            $map['id'] = $item['id'];
            Db::startTrans();
            try {
                $nfee = Db::table('activity')
                    ->where($map)
                    ->update(['up' => 1]);
                if ($nfee !== 1) {
                    throw new \Exception('更新事件失败' . json_encode($map));
                }
                $nfee = Db::table('member')
                    ->where(['unique_name' => $item['unique_name']])
                    ->setField('bonus', ['exp', 'bonus+(' . $bonus . ')']);
                if ($nfee !== 1) {
                    throw new \Exception('更新活动失败' . json_encode($item));
                }
                Db::commit();
                if ($item['code'] !== null) {
                    $cardup = CardOper::update(
                        $item['unique_name'],
                        $item['code'],
                        $bonus,
                        intval($item['bonus']) + $bonus,
                        '活动记录变更');
                    if ($cardup !== true) {
                        return $cardup;
                    }
                }
            } catch (\Exception $e) {
                Db::rollback();
                return json(['msg' => '' . $e], 400);
            }
        }
        return json(['msg' => 'ok', 'c' => count($item)]);
    }
}

class OrderOper
{
    const FEE_YEAR = [
        ['label' => '续费一年-原价', 'value' => 0, 'fee' => 30],
        ['label' => '续费二年-83折', 'value' => 1, 'fee' => 50],
        ['label' => '续费三年-66折', 'value' => 2, 'fee' => 60]
    ];

    /**
     *
     * @param \app\WxPayUnifiedOrder $input
     * @param int $year
     * @return bool|\app\WxPayUnifiedOrder
     */
    public static function fee($input, $year)
    {
        $fee = OrderOper::FEE_YEAR[$year]['fee'] * 100;
        $label = OrderOper::FEE_YEAR[$year]['label'];
        $openid = session('openid');
        $input->SetBody("会员缴费");
        $input->SetDetail('会员缴费：' . $label);
        $input->SetTotal_fee('' . $fee);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $map['openid'] = $openid;
        $map['fee'] = $fee;
        $map['type'] = 1;
        $map['value'] = $year;
        $map['trans'] = '';
        $res = Db::table('order')
            ->where($map)
            ->field([
                'outid'
            ])
            ->find();
        if (null === $res) {
            $outid = session('card') . date("YmdHis");
            $map['label'] = $label;
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
}