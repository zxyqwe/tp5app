<?php

namespace hanbj\weixin;

use hanbj\MemberOper;
use think\Db;
use think\exception\HttpResponseException;
use hanbj\vote\WxOrg;
use hanbj\CardOper;
use hanbj\UserOper;
use util\MysqlLog;
use wxsdk\WxTokenJsapi;
use wxsdk\WxTokenTicketapi;

class WxHanbj
{
    public static function json_wx($url)
    {
        $wx['api'] = config('hanbj_api');
        $wx['timestamp'] = time();
        $wx['nonce'] = getNonceStr();
        $ss = 'jsapi_ticket=' . self::jsapi() .
            '&noncestr=' . $wx['nonce'] .
            '&timestamp=' . $wx['timestamp']
            . '&url=' . $url;
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        $wx['cur_url'] = $url;
        return json_encode($wx);
    }

    public static function jsapi()
    {
        $token = new WxTokenJsapi('HANBJ_JSAPI', config('hanbj_api'), config('hanbj_secret'));
        return $token->get();
    }

    public static function ticketapi()
    {
        $token = new WxTokenTicketapi('HANBJ_TICKETAPI', config('hanbj_api'), config('hanbj_secret'));
        return $token->get();
    }

    public static function addUnionID($access, $limit = 15)
    {
        $ret = Db::table('member')
            ->where([
                'openid' => ['exp', Db::raw('is not null')],
                'unionid' => ['exp', Db::raw('is null')]
            ])
            ->limit($limit)
            ->field(['openid'])
            ->select();
        $user = [];
        foreach ($ret as $idx) {
            if (!cache("?addUnionID{$idx['openid']}")) {
                $user[] = $idx;
            }
        }
        if (count($user) == 0) {
            return 0;
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=' . $access;
        $data = ['user_list' => $user];
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if (!isset($res['user_info_list'])) {
            trace("addUnionID $raw", MysqlLog::ERROR);
            return $limit;
        }

        $limit = count($user);
        foreach ($res['user_info_list'] as $idx) {
            if (!isset($idx['unionid'])) {
                cache("addUnionID{$idx['openid']}", "addUnionID{$idx['openid']}", 3600);
                continue;
            }
            $ret = Db::table('member')
                ->where([
                    'openid' => $idx['openid'],
                    'unionid' => ['exp', Db::raw('is null')]
                ])
                ->data(['unionid' => $idx['unionid']])
                ->update();
            if ($ret > 0) {
                trace("addUnionID $ret {$idx['openid']} -- {$idx['unionid']}", MysqlLog::INFO);
                $limit -= $ret;
            }
        }
        return $limit;
    }

    public static function handle_msg($msg)
    {
        $msg = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        $type = (string)$msg->MsgType;
        $from = (string)$msg->FromUserName;
        $to = (string)$msg->ToUserName;
        $unique_name = '';
        if (cache("?chatbot$from")) {
            $unique_name = cache("chatbot$from");
        }
        $welcome = "欢迎关注，此服务号仅做汉北平台技术支持使用，无人值守，全程自动。如需交流请关注北京汉服协会（筹），微信搜: hanfubeijing\n\n点击这里进入微店<a href=\"https://weidian.com/?userid=1353579309\">汉服北京的小店</a>~";
        switch ($type) {
            case 'event':
                return self::do_event($msg, $unique_name);
            case 'text':
                $cont = (string)$msg->Content;
                $old_cont = $cont;
                if (in_array($cont, ['买', '推', '订'])) {
                    trace("跳过关键词 $unique_name $cont", MysqlLog::LOG);
                    return '';
                } elseif ($cont === '投票') {
                    $cont = "检查口令......成功\n";
                    foreach (WxOrg::vote_cart as $item) {
                        $org = new WxOrg(intval($item));
                        $cont .= $org->listobj($unique_name);
                    }
                    return self::auto($from, $to, $cont, '投票');
                } elseif (strlen($cont) === 4 && is_numeric($cont) && cache("?tempnum$cont")) {
                    $cont = cache("tempnum$cont");
                    $cont = self::tempid(json_decode($cont, true));
                    return self::auto($from, $to, $cont, "临时身份 $unique_name $old_cont");
                } elseif (!empty($unique_name)) {
                    try {
                        $cont = Curl_Get('http://127.0.0.1:9999/bbb?aaa=' . rawurlencode($cont));
                        $cont = json_decode($cont, true);
                        $cont = $cont['msg'];
                    } catch (HttpResponseException $e) {
                        $cont = '机器人不在线';
                        define('TAG_TIMEOUT_EXCEPTION', true);
                    }
                    $cont = "检查口令......失败\n身份验证......成功\n\n$unique_name\n文字信息：$old_cont\n\n$cont\n\n$welcome";
                    return self::auto($from, $to, $cont);
                }
                $cont = "检查口令......失败\n身份验证......失败\n\n文字信息：$cont\n\n$welcome";
                return self::auto($from, $to, $cont);
            default:
                trace(json_encode($msg), MysqlLog::ERROR);
            case 'image':
            case 'voice':
            case 'video':
            case 'shortvideo':
            case 'location':
            case 'link':
                return self::auto($from, $to, "$type $unique_name");
        }
    }

    private static function tempid($data)
    {
        $cont = "检查口令......成功\n身份验证......成功\n\n临时身份信息验证\n会员编号：{$data['uniq']}\n" .
            "昵称：{$data['nick']}\n" .
            "生成日期：{$data['time']}\n" .
            "生成时间：{$data['time2']}\n" .
            "有效期：30分钟";
        return $cont;
    }

    private static function auto($to, $from, $type, $debug_msg = '')
    {
        if (empty($debug_msg)) {
            trace("TO => $to, TEXT => " . str_replace("\n", '|', $type), MysqlLog::INFO);
        } else {
            trace($to . ' ' . str_replace("\n", '|', $debug_msg), MysqlLog::LOG);
        }
        $data = '<xml>' .
            '<ToUserName><![CDATA[%s]]></ToUserName>' .
            '<FromUserName><![CDATA[%s]]></FromUserName>' .
            '<CreateTime>%s</CreateTime>' .
            '<MsgType><![CDATA[text]]></MsgType>' .
            '<Content><![CDATA[******机器人自动回复******%s]]></Content>' .
            '</xml>';
        return sprintf($data, $to, $from, time(), "\n" . $type);
    }

    private static function do_event($msg, $unique_name)
    {
        $type = (string)$msg->Event;
        $from = (string)$msg->FromUserName;
        trace("WxEvent $unique_name $from $type", MysqlLog::LOG);
        switch ($type) {
            case 'user_del_card':
                return CardOper::del_card($msg);
            case 'user_get_card':
                return CardOper::get_card($msg);
            case 'TEMPLATESENDJOBFINISH':
                $Status = (string)$msg->Status;
                if ('success' != $Status) {
                    trace($unique_name . json_encode($msg), MysqlLog::ERROR);
                }
                MemberOper::try_junior($from);
                return '';
            default:
                trace($unique_name . json_encode($msg), MysqlLog::ERROR);
            case 'update_member_card':
            case 'subscribe':
            case 'unsubscribe':
            case 'SCAN':
            case 'LOCATION':
            case 'CLICK':
            case 'VIEW':
            case 'user_view_card':
            case 'user_gifting_card':
            case 'user_enter_session_from_card':
            case 'card_sku_remind':
            case 'MASSSENDJOBFINISH':
                return '';
        }
    }

    public static function setJump($event, $item, $uname, $expire)
    {
        $nonce = getNonceStr() . $uname . $event . $item . $expire;
        $nonce = md5($nonce);
        $data['event'] = $event;
        $data['val'] = $item;
        cache('jump' . $nonce, json_encode($data), $expire);
        return $nonce;
    }

    public static function jump($nonce)
    {
        $obj = cache('jump' . $nonce);
        if (false !== $obj) {
            $obj = json_decode($obj, true);
            switch ($obj['event']) {
                case 'wxtest':
                    return redirect('https://app.zxyqwe.com/hanbj/wxtest/index/obj/' . $nonce);
                case 'login':
                    UserOper::nonce($nonce);
                    return redirect('https://app.zxyqwe.com/hanbj/mobile');
            }
        }
        return view('jump');
    }
}
