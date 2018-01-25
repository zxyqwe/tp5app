<?php

namespace app\hanbj\controller;

use hanbj\BonusOper;
use hanbj\MemberOper;
use hanbj\UserOper;
use hanbj\vote\WxVote;
use app\SHA1;
use app\WXBizMsgCrypt;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use think\Response;
use hanbj\FeeOper;
use hanbj\weixin\WxHanbj;
use hanbj\CardOper;

class Mobile extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'index,reg,event,help']
    ];

    protected function valid_id()
    {
        if (!MemberOper::wx_login()) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return json([], 404);
    }

    public function index($obj = '')
    {
        if (!MemberOper::wx_login()) {
            $prefix = empty($obj) ? '' : '/index/obj/' . $obj;
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile' . $prefix, config('hanbj_api'));
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
                'bonus',
                'phone'
            ])
            ->find();
        if (null === $res) {
            return redirect('https://app.zxyqwe.com/hanbj/mobile/reg');
        }
        $url = 'https://app.zxyqwe.com' . $_SERVER['REQUEST_URI'];
        session('json_wx', WxHanbj::json_wx($url));
        session('unique_name', $res['unique_name']);
        session('tieba_id', $res['tieba_id']);
        session('member_code', $res['code']);
        if (in_array(intval($res['code']), MemberOper::getMember())) {
            cache("chatbot$openid", $res['unique_name']);
        }
        UserOper::login();
        $res['bonus_top'] = BonusOper::mod_ret($res['bonus']);
        $res['code'] = MemberOper::trans($res['code']);
        $res['fee_code'] = FeeOper::cache_fee(session('unique_name'));
        $res['phone'] = preg_replace('/(\d{3})\d{4}(\d{4})/', "$1****$2", $res['phone']);
        if (!empty($obj)) {
            return WxHanbj::jump($obj);
        }
        return view('home', [
            'user' => $res,
            'card' => CardOper::mod_ret($map),
            'worker' => in_array($res['unique_name'], BonusOper::getWorkers()) ? 1 : 0,
            'status' => $res['fee_code'] >= date('Y'),
            'vote' => WxVote::result($res['unique_name'])
        ]);
    }

    public function reg()
    {
        if (session('?tieba_id') && session('unique_name') !== '坎丙午') {
            return redirect('https://app.zxyqwe.com/hanbj/mobile');
        }
        if (!MemberOper::wx_login()) {
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile/reg', config('hanbj_api'));
        }
        return view('reg');
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
            trace("绑定 $phone " . session('openid'));
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
        if ($card === null) {
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

    public function unused()
    {
        switch ($this->request->method()) {
            case 'GET':
                $ret = MemberOper::list_code(MemberOper::UNUSED);
                $rst = [];
                foreach ($ret as $i) {
                    if (false !== strpos($i, '夏')
                        || false !== strpos($i, '商')
                    ) {
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
                    session('unique_name', $unique_name);
                    return json(['msg' => 'ok']);
                }
                return json(['msg' => '会员编号没抢到'], 400);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    public function help()
    {
        return view('help');
    }
}
