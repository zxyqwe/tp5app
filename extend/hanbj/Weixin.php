<?php

namespace hanbj;

use app\WxPayOrderQuery;
use app\WxPayApi;
use app\WxPayDataBase;
use app\WxPayNotify;
use think\exception\HttpResponseException;

class WxHanbj
{
    public static function json_wx($url)
    {
        $wx['api'] = config('hanbj_api');
        $wx['timestamp'] = time();
        $wx['nonce'] = getNonceStr();
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $ss = 'jsapi_ticket=' . self::jsapi($access) .
            '&noncestr=' . $wx['nonce'] .
            '&timestamp=' . $wx['timestamp']
            . '&url=' . $url;
        $ss = sha1($ss);
        $wx['signature'] = $ss;
        $wx['cur_url'] = $url;
        return json_encode($wx);
    }

    public static function jsapi($access)
    {
        if (cache('?jsapi')) {
            return cache('jsapi');
        }
        $raw = Curl_Get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $access . '&type=jsapi');
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("JsApi $raw");
            return '';
        }
        trace("WxHanbj JsApi {$res['ticket']}");
        cache('jsapi', $res['ticket'], $res['expires_in'] - 10);
        return $res['ticket'];
    }

    public static function ticketapi($access)
    {
        if (cache('?ticketapi')) {
            return cache('ticketapi');
        }
        $raw = Curl_Get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $access . '&type=wx_card');
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("TicketApi $raw");
            return '';
        }
        trace("WxHanbj TicketApi {$res['ticket']}");
        cache('ticketapi', $res['ticket'], $res['expires_in'] - 10);
        return $res['ticket'];
    }

    public static function handle_msg($msg)
    {
        $msg = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        $type = (string)$msg->MsgType;
        $from = (string)$msg->FromUserName;
        $to = (string)$msg->ToUserName;
        switch ($type) {
            case 'event':
                return self::do_event($msg);
            default:
                trace(json_encode($msg));
            case 'text':
                $cont = (string)$msg->Content;
                if ($cont === '投票') {
                    $org = new WxOrg();
                    $cont = $org->listobj($from);
                    return self::auto($from, $to, $cont, '投票');
                } elseif (strlen($cont) === 4 && is_numeric($cont) && cache('?tempnum' . $cont)) {
                    $cont = cache('tempnum' . $cont);
                    $cont = self::tempid(json_decode($cont, true));
                    return self::auto($from, $to, $cont, '临时身份');
                } elseif (cache("?chatbot$from")) {
                    try {
                        $cont = Curl_Get('http://127.0.0.1:9999/bbb?aaa=' . rawurlencode($cont));
                        $cont = json_decode($cont, true);
                        $cont = $cont['msg'];
                    } catch (HttpResponseException $e) {
                        $cont = '机器人不在线';
                    }
                    $cont = "检查口令...失败\n身份验证...成功\n\n" . $cont;
                    return self::auto($from, $to, $cont);
                }
                $cont = "检查口令...失败\n身份验证...失败\n\n文字信息：" . $cont;
                return self::auto($from, $to, $cont);
            case 'image':
            case 'voice':
            case 'video':
            case 'shortvideo':
            case 'location':
            case 'link':
                return self::auto($from, $to, $type);
        }
    }

    private static function tempid($data)
    {
        $cont = "检查口令...成功\n身份验证...成功\n\n临时身份信息验证\n会员编号：{$data['uniq']}\n" .
            "昵称：{$data['nick']}\n" .
            "生成日期：{$data['time']}\n" .
            "生成时间：{$data['time2']}\n" .
            "有效期：30分钟";
        return $cont;
    }

    private static function auto($to, $from, $type, $debug_msg = '')
    {
        if (empty($debug_msg)) {
            trace("TO => $to, TEXT => " . preg_replace('/\s*/', '', $type));
        } else {
            trace($to . ' ' . preg_replace('/\s*/', '', $debug_msg));
        }
        $data = '<xml>' .
            '<ToUserName><![CDATA[%s]]></ToUserName>' .
            '<FromUserName><![CDATA[%s]]></FromUserName>' .
            '<CreateTime>%s</CreateTime>' .
            '<MsgType><![CDATA[text]]></MsgType>' .
            '<Content><![CDATA[***机器人自动回复***%s]]></Content>' .
            '</xml>';
        return sprintf($data, $to, $from, time(), "\n" . $type);
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
            case 'unsubscribe':
            case 'SCAN':
            case 'LOCATION':
            case 'CLICK':
            case 'VIEW':
            case 'user_view_card':
            case 'user_gifting_card':
            case 'user_enter_session_from_card':
            case 'card_sku_remind':
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

class HanbjNotify extends WxPayNotify
{
    public function Queryorder($out_trade_no)
    {
        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($out_trade_no);
        $result = WxPayApi::orderQuery($input);
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
        ) {
            return true;
        }
        return false;
    }

    public function NotifyProcess($data, &$msg)
    {
        $msg = 'OK';
        if (!array_key_exists("out_trade_no", $data)) {
            return false;
        }
        //查询订单，判断订单真实性
        if (!$this->Queryorder($data["out_trade_no"])) {
            return false;
        }
        return OrderOper::handle($data);
    }
}

class HanbjRes extends WxPayDataBase
{
    public function setValues($value)
    {
        $this->values = $value;
        return $this->MakeSign();
    }
}


class WxTemp
{
    public static function notifyFee($openid, $uname, $fee, $cache_fee, $label)
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access;
        $data = [
            "touser" => $openid,
            "template_id" => "WBIYdFZfjU7nE5QkL9wjYF6XUkUlQXKQblN5pvegtMw",
            "url" => "https://app.zxyqwe.com/hanbj/mobile",
            "topcolor" => "#FF0000",
            "data" => [
                "first" => [
                    "value" => "您好，您已成功进行北京汉服协会（筹）会员缴费。"
                ],
                "accountType" => [
                    "value" => '会员编号'
                ],
                'account' => [
                    'value' => $uname,
                    "color" => "#173177"
                ],
                'amount' => [
                    'value' => $fee . '元'
                ],
                'result' => [
                    'value' => '缴至' . $cache_fee,
                    "color" => "#173177"
                ],
                'remark' => [
                    'value' => '明细：' . $label . '。积分将在核实后到账，请稍后。'
                ]
            ]
        ];
        $log = implode(', ', [$openid, $uname, $fee, $cache_fee, $label]);
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("notifyFee $log $raw");
        } else {
            trace($log);
        }
    }

    public static function regAct($openid, $uname, $act)
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access;
        $data = [
            "touser" => $openid,
            "template_id" => "pAg9VfUQYxgGfVmceEpw_AXiLPEXb7Ug4pamcG45d-A",
            "url" => "https://app.zxyqwe.com/hanbj/mobile",
            "topcolor" => "#FF0000",
            "data" => [
                "first" => [
                    "value" => "您好，您已成功进行北京汉服协会（筹）活动登记。"
                ],
                "keyword1" => [
                    "value" => $act
                ],
                'keyword2' => [
                    'value' => $uname . '-成功',
                    "color" => "#173177"
                ],
                'remark' => [
                    'value' => '积分将在核实后到账，请稍后。'
                ]
            ]
        ];
        $log = implode(', ', [$openid, $uname, $act]);
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("regAct $log $raw");
        } else {
            trace($log);
        }
    }
}