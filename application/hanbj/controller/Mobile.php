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
            return WX_redirect('/hanbj/mobile', config('hanbj_api'));
        }
        $openid = session('openid');
        $map['c.openid'] = $openid;
        $res = Db::table('card')
            ->alias('c')
            ->join('member m', 'm.mcode=c.mcode')
            ->where($map)
            ->cache(86400)
            ->field([
                ''
            ])
            ->find();
        if (null === $res) {
            return view('reg');
        }
        return view('home', ['user' => $res]);
    }

    public function event()
    {
        //$access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        //return substr($access, 0, 5);
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
        $data = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[机器人自动回复：%s]]></Content></xml>';
        return sprintf($data, $to, $from, time(), $type);
    }

    private function do_event($msg)
    {
        $type = (string)$msg->Event;
        switch ($type) {
            default:
                trace(json_encode($msg));
            case 'subscribe':
            case 'SCAN':
            case 'LOCATION':
            case 'CLICK':
            case 'VIEW':
                //case 'card_pass_check':
            case 'user_gifting_card':
                //case 'user_del_card':
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
