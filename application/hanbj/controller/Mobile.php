<?php

namespace app\hanbj\controller;

use app\hanbj\BonusOper;
use app\SHA1;
use app\WXBizMsgCrypt;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use think\Response;
use app\hanbj\FeeOper;
use app\hanbj\WxHanbj;
use app\hanbj\CardOper;

class Mobile extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'index,reg,event']
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

    public function index()
    {
        if (!WX_iter(config('hanbj_api'), config('hanbj_secret'))) {
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile', config('hanbj_api'));
        }
        if (input('?get.encrypt_code')) {
            return redirect('https://app.zxyqwe.com/hanbj/mobile');
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
            return redirect('https://app.zxyqwe.com/hanbj/mobile/reg');
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
            $card = ['status' => -1];
        } else {
            session('card', $card['code']);
        }
        return view('home', [
            'user' => $res,
            'card' => $card['status'],
            'worker' => in_array($res['unique_name'], BonusOper::WORKER) ? 1 : 0,
            'status' => $res['fee_code'] >= date('Y')
        ]);
    }

    public function reg()
    {
        if (!WX_iter(config('hanbj_api'), config('hanbj_secret'))) {
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile', config('hanbj_api'));
        }
        if (input('?get.encrypt_code')) {
            return redirect('https://app.zxyqwe.com/hanbj/mobile');
        }
        return view('reg');
    }

    public function json_wx()
    {
        $wx['api'] = config('hanbj_api');
        $wx['timestamp'] = time();
        $wx['nonce'] = getNonceStr();
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $ss = 'jsapi_ticket=' . WxHanbj::jsapi($access) .
            '&noncestr=' . $wx['nonce'] .
            '&timestamp=' . $wx['timestamp']
            . '&url=' . urldecode($_GET['url']);
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        return json($wx);
    }

    public function json_old()
    {
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
        $wx['card_id'] = config('hanbj_cardid');
        $wx['timestamp'] = '' . time();
        $wx['nonce_str'] = getNonceStr();
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $ss = [$wx['nonce_str'],
            $wx['timestamp'],
            WxHanbj::ticketapi($access),
            $wx['card_id']];
        sort($ss);
        $ss = implode('', $ss);
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        return json($wx);
    }

    public function event()
    {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            return '';
        }
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
