<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'wx.php';

use app\SHA1;
use app\WXBizMsgCrypt;
use think\Db;

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
        //simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
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
            return 'fail';
        }
        $pc = new WXBizMsgCrypt($token, $aes, $api);
        $msg = '';
        $err = $pc->decryptMsg($msg_sign, $timestap, $nonce, $post_data, $msg);
        if ($err !== 0) {
            trace(['dec', $err]);
        }
        trace($msg);
        $reply = '';
        $err = $pc->encryptMsg($msg, time(), getNonceStr(), $reply);
        if ($err !== 0) {
            trace(['enc', $err]);
        }
        return $reply;
    }
}
