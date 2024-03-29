<?php

namespace app\hanbj\controller;

use DateTimeImmutable;
use Exception;
use hanbj\TodoOper;
use hanbj\vote\WxOrg;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use think\response\Json;
use think\response\Redirect;
use think\response\View;
use util\MysqlLog;
use wxsdk\mp\SHA1;
use wxsdk\mp\WXBizMsgCrypt;
use hanbj\BonusOper;
use hanbj\CardOper;
use hanbj\FeeOper;
use hanbj\MemberOper;
use hanbj\HBConfig;
use hanbj\UserOper;
use hanbj\weixin\WxHanbj;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use think\Response;

class Mobile extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'index,reg,event,help,rpcauth,simplevote'],
    ];

    protected function valid_id()
    {
        if (!UserOper::wx_login()) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return json([], 404);
    }

    public function logout()
    {
        session(null);
        cache("set_fake_wx_id", null);
        return WX_redirect('https://app.zxyqwe.com/hanbj/mobile', config('hanbj_api'));
    }

    /**
     * @param string $obj
     * @return Redirect|View
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function index($obj = '')
    {
        if (!UserOper::wx_login()) {
            trace("没微信授权 index", MysqlLog::LOG);
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
                'start_time',
                'code',
                'master',
                'tieba_id',
                'gender',
                'QQ',
                'mail',
                'pref',
                'web_name',
                'bonus',
                'phone',
                'birth',
                'location',
            ])
            ->find();
        if (null === $res) {
            trace("没注册 $openid", MysqlLog::LOG);
            session(null);
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
        $res['fee_code'] = FeeOper::cache_fee($res['unique_name']);
        $res['fee_str'] = $res['fee_code']->format('Y-m-d H:i:s');
        $res['phone'] = preg_replace('/(\d{3})\d{4}(\d{4})/', "$1****$2", $res['phone']);
        $res['duration'] = MemberOper::calc_duration($res['start_time'])->days;
        $res['event'] = MemberOper::event($res['unique_name']);
        if (!empty($obj)) {
            return WxHanbj::jump($obj);
        }
        trace("微信首页 {$res['unique_name']}", MysqlLog::LOG);
        return view('home', [
            'user' => $res,
            'card' => CardOper::mod_ret($map),
            'worker' => in_array($res['unique_name'], BonusOper::getWorkers()) ? 1 : 0,
            'status' => $res['fee_code'] >= new DateTimeImmutable(),
        ]);
    }

    public function reg()
    {
        if (session('?tieba_id') && session('unique_name') !== HBConfig::CODER) {
            trace("已注册 " . session('unique_name') . ' ' . session('tieba_id'), MysqlLog::LOG);
            return redirect('https://app.zxyqwe.com/hanbj/mobile');
        }
        if (!UserOper::wx_login()) {
            trace("没微信授权 reg", MysqlLog::LOG);
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile/reg', config('hanbj_api'));
        }
        return view('reg');
    }

    public function rpcauth()
    {
        $callback = input("get.callback");
        $callback = urldecode($callback);
        $callback = filter_var($callback, FILTER_SANITIZE_URL);
        if (!preg_match("/^(https:\/\/(\w\w*\.)?hanfubj\.com|https?:\/\/localhost(:\d\d*)?)(\/.*)?$/i", $callback)) {
            return json(["msg" => $callback], 400);
        }

        $register = input("get.register");
        $register = urldecode($register);
        $register = filter_var($register, FILTER_SANITIZE_URL);
        if (!preg_match("/^(https:\/\/active\.qunliaoweishi\.com|https?:\/\/localhost(:\d\d*)?)(\/.*)?$/i", $register)) {
            return json(["msg" => $register], 400);
        }

        if (!UserOper::wx_login()) {
            session(null);
            return WX_redirect('https://app.zxyqwe.com' . $_SERVER["REQUEST_URI"], config('hanbj_api'), '', 'snsapi_userinfo');
        }

        $openid = session('openid');
        $user_info = [];
        if (!cache("?user_info$openid")) {
            WX_union(session('access_token'), $openid, $user_info);
        } else {
            $user_info = json_decode(cache("user_info$openid"), true);
        }
        unset($user_info['privilege']);

        $redirect_data = [];
        $raw = Curl_Post($user_info, $register);
        $data = json_decode($raw, true);
        if (
            !isset($data['code']) ||
            $data['code'] !== 0 ||
            !isset($data['data']) ||
            !isset($data['data']['token'])
        ) {
            trace("RpcAuth $register $raw " . json_encode($user_info), MysqlLog::ERROR);
        } else {
            trace("RpcAuth Read " . json_encode($user_info), MysqlLog::INFO);
            $redirect_data = ["token" => $data['data']['token']];
        }
        return redirect($callback . "?" . http_build_query($redirect_data));
    }

    public function json_old()
    {
        $phone = input('post.phone', FILTER_VALIDATE_INT);
        $eid = strtolower(input('post.eid'));
        $map['phone'] = $phone;
        $map['openid'] = ['exp', Db::raw('is null')];
        $res = Db::table('member')
            ->where($map)
            ->value('eid');
        if (strlen($res) < 6) {
            return json(['msg' => '手机号错误'], 400);
        }
        if (substr($res, strlen($res) - 6) !== $eid) {
            return json(['msg' => '身份证错误'], 400);
        }
        $res = Db::table('member')
            ->where($map)
            ->setField('openid', session('openid'));
        if ($res !== 1) {
            trace([$phone, session('openid')], MysqlLog::ERROR);
            return json(['msg' => '绑定失败'], 500);
        }
        trace("绑定 $phone " . session('openid'), MysqlLog::INFO);
        return json(['msg' => 'OK']);
    }

    public function json_card()
    {
        $openid = session('openid');
        $map['openid'] = $openid;
        $map['card_id'] = config('hanbj_cardid');
        $card = Db::table('card')
            ->where($map)
            ->value('code');
        $wx['code'] = $card;
        $wx['card'] = config('hanbj_cardid');
        return json($wx);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function json_active()
    {
        $openid = session('openid');
        $map['openid'] = $openid;
        $map['status'] = 0;
        $map['card_id'] = config('hanbj_cardid');
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
        $ss = [$wx['nonce_str'],
            $wx['timestamp'],
            WxHanbj::ticketapi(),
            $wx['card_id']];
        sort($ss);
        $ss = implode('', $ss);
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        return json($wx);
    }

    /**
     * @return string|Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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
            trace(['dec', $err], MysqlLog::ERROR);
            return new Response('', 404);
        }

        $msg = WxHanbj::handle_msg($msg);
        if (empty($msg)) {
            return '';
        }

        $reply = '';
        $err = $pc->encryptMsg($msg, time(), getNonceStr(), $reply);
        if ($err !== 0) {
            trace(['enc', $err], MysqlLog::ERROR);
            return new Response('', 404);
        }
        return $reply;
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function unused()
    {
        switch ($this->request->method()) {
            case 'GET':
                $rst = MemberOper::get_open();
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
                    session('tieba_id', $tieba_id);
                    $limit = WxHanbj::addUnionID(WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS'));
                    if ($limit > 0) {
                        trace("未关注者：$limit", MysqlLog::LOG);
                    }
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

    /**
     * @return Json
     * @throws \think\Exception
     * @throws PDOException
     */
    public function todo()
    {
        switch ($this->request->method()) {
            case 'GET':
                $ret = TodoOper::showTodo();
                return json(['msg' => $ret]);
                break;
            case 'POST':
                $type = input('post.type', 0, FILTER_VALIDATE_INT);
                $key = input('post.key', 0, FILTER_VALIDATE_INT);
                $res = input('post.res', 0, FILTER_VALIDATE_INT);

                $done = TodoOper::handleTodo($type, $key, $res);
                if (!$done) {
                    throw new HttpResponseException(json(['msg' => "handleTodo($type, $key, $res) no update"]));
                }

                $ret = TodoOper::showTodo();
                return json(['msg' => $ret]);
                break;
            default:
                return json(['msg' => '错误 ' . $this->request->method()]);
        }
    }

    /**
     * @return void
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function simplevote()
    {
        if (!UserOper::wx_login()) {
            trace("没微信授权 simplevote", MysqlLog::ERROR);
            return WX_redirect('https://app.zxyqwe.com/hanbj/mobile', config('hanbj_api'));
        }
        $cont = "";
        $unique_name = session('unique_name');
        foreach (WxOrg::vote_cart as $item) {
            $org = new WxOrg(intval($item));
            $cont .= $org->listobj($unique_name);
        }
        echo str_replace("\n", "<br />", $cont);
        return;
    }
}
