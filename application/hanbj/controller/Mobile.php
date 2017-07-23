<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'wx.php';

use app\SHA1;
use app\WXBizMsgCrypt;
use think\Db;
use think\Response;

class Mobile
{
    public function index()
    {
        if (!WX_iter(config('hanbj_api'), config('hanbj_secret'))) {
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile', config('hanbj_api'));
        }
        $openid = session('openid');
        $map['openid'] = $openid;
        $res = Db::table('member')
            ->alias('m')
            ->where($map)
            ->cache(600)
            ->field([
                'unique_name',
                'year_time',
                'code',
                'master'
            ])
            ->find();
        if (null === $res) {
            return view('reg');
        }
        session('unique_name', $res['unique_name']);
        switch ($res['code']) {
            case 0:
                $res['code'] = '正常';
                break;
            case 1:
                $res['code'] = '注销';
                break;
        }
        $card = Db::table('card')
            ->where($map)
            ->value('status');
        if ($card === false) {
            $card = -1;
        }
        return view('home', ['user' => $res, 'card' => $card]);
    }

    public function json_wx()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $openid = session('openid');
        $map['openid'] = $openid;
        $card = Db::table('card')
            ->where($map)
            ->value('code');
        $wx['code'] = $card;
        $wx['card'] = config('hanbj_cardid');
        $wx['api'] = config('hanbj_api');
        $wx['timestamp'] = time();
        $wx['nonce'] = getNonceStr();
        $ss = 'jsapi_ticket=' . $this->jsapi() .
            '&noncestr=' . $wx['nonce'] .
            '&timestamp=' . $wx['timestamp']
            . '&url=' . urldecode(input('get.url'));
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        return json($wx);
    }

    private function jsapi()
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

    public function access()
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        return substr($access, 0, 5);
    }

    public function img()
    {
        if (cache('?HANBJ_CARD')) {
            return redirect(cache('HANBJ_CARD'));
        }
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/qrcode/create?access_token=' . $access;
        $data = [
            "action_name" => "QR_CARD",
            "action_info" => [
                "card" => [
                    "card_id" => config('hanbj_cardid')
                ]
            ]
        ];
        $res = Curl_Post($data, $url, false);
        $res = json_decode($res, true);
        if ($res['errcode'] !== 0) {
            return json_encode($res);
        }
        cache('HANBJ_CARD', $res['show_qrcode_url'], $res['expire_seconds']);
        return redirect($res['show_qrcode_url']);
    }

    public function json_old()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $phone = input('post.phone');
        $eid = input('post.eid');
        $map['phone'] = $phone;
        $map['openid'] = ['EXP', 'IS NULL'];
        $res = Db::table('member')
            ->where($map)
            ->value('eid');
        if (strlen($res) < 6) {
            return json(['msg' => '手机号错误'], 400);
        }
        if (substr($res, strlen($res) - 6) === $eid) {
            $res = Db::table('member')
                ->where($map)
                ->setField('openid', session('openid'));
            if ($res !== 1) {
                trace([$phone, session('openid')]);
                return json(['msg' => '绑定失败'], 500);
            }
            return json(['msg' => 'OK']);
        }
        return json(['msg' => '身份证错误'], 400);
    }

    public function json_card()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $openid = session('openid');
        $map['openid'] = $openid;
        $map['status'] = 0;
        $card = Db::table('card')
            ->where($map)
            ->value('code');
        if ($card === false) {
            return json(['msg' => '没有未激活会员卡'], 400);
        }
        return $this->active($card);
    }

    public function json_view()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $openid = session('openid');
        $map['openid'] = $openid;
        $card = Db::table('card')
            ->where($map)
            ->value('code');
        if ($card === false) {
            return json(['msg' => '没有会员卡'], 400);
        }
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/membercard/userinfo/get?access_token=' . $access;
        $data = [
            "code" => $card,
            "card_id" => config('hanbj_cardid')
        ];
        $res = Curl_Post($data, $url, false);
        return json(['msg' => $res]);
    }

    public function event()
    {
        $token = config('hanbj_token');
        $aes = config('hanbj_EncodingAESKey');
        $api = config('hanbj_api');
        $post_data = $GLOBALS["HTTP_RAW_POST_DATA"];
        $msg_sign = input('get.msg_signature');
        $sign = input('get.signature');
        $timestap = input('get.timestamp');
        $nonce = input('get.nonce');

        $pc = new SHA1();
        $err = $pc->getSHA1($token, $timestap, $nonce, '');
        if ($err[1] !== $sign) {
            return new Response('', 404);
        }

        $pc = new WXBizMsgCrypt($token, $aes, $api);
        $msg = '';
        $err = $pc->decryptMsg($msg_sign, $timestap, $nonce, $post_data, $msg);
        if ($err !== 0) {
            trace(['dec', $err]);
            return new Response('', 404);
        }

        $msg = $this->handle_msg($msg);
        if (empty($msg)) {
            return '';
        }

        $reply = '';
        $err = $pc->encryptMsg($msg, time(), getNonceStr(), $reply);
        if ($err !== 0) {
            trace(['enc', $err]);
            return new Response('', 404);
        }
        return $reply;
    }

    private function handle_msg($msg)
    {
        $msg = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        $type = (string)$msg->MsgType;
        switch ($type) {
            case 'event':
                return $this->do_event($msg);
            default:
                trace(json_encode($msg));
            case 'text':
            case 'image':
            case 'voice':
            case 'video':
            case 'shortvideo':
            case 'location':
            case 'link':
                return $this->auto((string)$msg->FromUserName, (string)$msg->ToUserName, $type);
        }
    }

    private function auto($to, $from, $type)
    {
        trace(implode(':', [
            $to,
            $type
        ]));
        $data = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[机器人自动回复：%s]]></Content></xml>';
        return sprintf($data, $to, $from, time(), $type);
    }

    private function do_event($msg)
    {
        $type = (string)$msg->Event;
        switch ($type) {
            case 'user_del_card':
                return $this->del_card($msg);
            case 'user_get_card':
                return $this->get_card($msg);
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

    private function del_card($msg)
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

    private function get_card($msg)
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

    private function active($code)
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/membercard/activate?access_token=' . $access;
        $data = [
            "membership_number" => $code,
            "code" => $code,
            "card_id" => config('hanbj_cardid'),
            'init_bonus' => 0,
            'init_custom_field_value1' => session('unique_name'),
            'init_custom_field_value2' => $this->cache_fee()
        ];
        $res = Curl_Post($data, $url, false);
        $res = json_decode($res, true);
        if ($res['errcode'] !== 0) {
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

    private function cache_fee()
    {
        $uname = session('unique_name');
        $map['unique_name'] = $uname;
        $map['unoper'] = ['EXP', 'IS NULL'];
        $res = Db::table('fee')
            ->alias('f')
            ->where($map)
            ->count('1');
        unset($map['unoper']);
        $year = Db::table('member')
            ->where($map)
            ->value('year_time');
        $fee = intval($year) + intval($res) - 1;
        cache('fee', $fee);
        return $fee;
    }
}
