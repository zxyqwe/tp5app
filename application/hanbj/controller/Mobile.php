<?php

namespace app\hanbj\controller;

include_once APP_PATH . 'hanbj/custom.php';
include_once APP_PATH . 'wx.php';

use app\SHA1;
use app\WXBizMsgCrypt;
use think\Db;
use think\Response;
use app\hanbj\FeeOper;
use app\hanbj\WxHanbj;
use app\hanbj\CardOper;

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
            ->field([
                'unique_name',
                'year_time',
                'code',
                'master',
                'tieba_id',
                'gender',
                'QQ',
                'mail',
                'pref',
                'web_name',
                'bonus'
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
        $res['fee_code'] = FeeOper::cache_fee(session('unique_name'));
        $card = Db::table('card')
            ->where($map)
            ->field([
                'status',
                'code'
            ])
            ->find();
        if ($card === null) {
            $card = -1;
        } else {
            session('card', $card['code']);
        }
        return view('home', [
            'user' => $res,
            'card' => $card['status'],
            'worker' => '' . in_array($res['unique_name'], config('hanbj_worker'))
        ]);
    }

    public function json_wx()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $wx['api'] = config('hanbj_api');
        $wx['timestamp'] = time();
        $wx['nonce'] = getNonceStr();
        $ss = 'jsapi_ticket=' . WxHanbj::jsapi() .
            '&noncestr=' . $wx['nonce'] .
            '&timestamp=' . $wx['timestamp']
            . '&url=' . urldecode(input('get.url'));
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        return json($wx);
    }

    public function access()
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        return substr($access, 0, 5);
    }

    public function json_old()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $phone = input('post.phone', FILTER_VALIDATE_INT);
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
        $card = Db::table('card')
            ->where($map)
            ->value('code');
        $wx['code'] = $card;
        $wx['card'] = config('hanbj_cardid');
        return json($wx);
    }

    public function json_active()
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
        return CardOper::active($card);
    }

    public function json_addcard()
    {
        if (!session('?openid')) {
            return json(['msg' => '未登录'], 400);
        }
        $wx['card_id'] = config('hanbj_cardid');
        $wx['timestamp'] = time();
        $wx['nonce_str'] = getNonceStr();
        $ss = [$wx['nonce_str'],
            '' . $wx['timestamp'],
            WxHanbj::ticketapi(),
            $wx['card_id']];
        sort($ss);
        $ss = implode('', $ss);
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        return json($wx);
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

        $msg = WxHanbj::handle_msg($msg);
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


}
