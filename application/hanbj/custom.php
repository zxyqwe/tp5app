<?php

namespace app\hanbj;

use think\Db;

class FeeOper
{
    public static function cache_fee($uname)
    {
        $map['unique_name'] = $uname;
        $res = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->field([
                'count(oper) as s',
                'sum(f.code) as n'
            ])
            ->find();
        $year = Db::table('member')
            ->where($map)
            ->value('year_time');
        $fee = intval($year) + intval($res['s']) - 2 * intval($res['n']) - 1;
        return $fee;
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
        cache('jsapi', $res['ticket'], $res['expires_in']);
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
        cache('ticketapi', $res['ticket'], $res['expires_in']);
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
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/membercard/activate?access_token=' . $access;
        $data = [
            "membership_number" => $code,
            "code" => $code,
            "card_id" => config('hanbj_cardid'),
            'init_bonus' => 0,
            'init_custom_field_value1' => session('unique_name'),
            'init_custom_field_value2' => FeeOper::cache_fee(session('unique_name'))
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
    public static function upFee()
    {
        $map['up'] = 0;
        $map['f.unique_name'] = '坎丙午';
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left'],
            ['card c', 'c.openid=m.openid', 'left']
        ];
        $res = Db::table('nfee')
            ->alias('f')
            ->order('f.id')
            ->limit(1)
            ->where($map)
            ->join($join)
            ->field([
                'f.id',
                'f.code as c',
                'm.unique_name',
                'm.openid',
                'm.bonus',
                'c.code'
            ])
            ->select();
        unset($map['f.unique_name']);
        foreach ($res as $item) {
            $bonus = 15;
            if ($item['c'] === '1') {
                $bonus = -$bonus;
            }
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
                        '会费记录');
                    if ($cardup !== true) {
                        return $cardup;
                    }
                }
            } catch (\Exception $e) {
                Db::rollback();
                return json(['msg' => '' . $e], 400);
            }
        }
        return json(['msg' => 'ok']);
    }

    public static function upAct()
    {
        return json(['msg' => 'ok']);
    }
}