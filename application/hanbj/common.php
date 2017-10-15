<?php

namespace app\hanbj;

include_once APP_PATH . 'wx.php';
include_once APP_PATH . 'hanbj/WxConfig.php';
include_once APP_PATH . 'WxPay.php';
use think\Db;
use app\WxPayOrderQuery;
use app\WxPayApi;
use app\WxPayDataBase;
use app\WxPayNotify;
use Exception;

class FeeOper
{
    const ADD = 0;

    public static function cache_fee($uname)
    {
        $cache_name = 'cache_fee' . $uname;
        if (cache('?' . $cache_name)) {
            return cache($cache_name);
        }
        $map['unique_name'] = $uname;
        $res = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->field([
                'sum(f.code) as n'
            ])
            ->find();
        $year = Db::table('member')
            ->where($map)
            ->value('year_time');
        $fee = intval($year) + intval($res['n']) - 1;
        cache($cache_name, $fee);
        return $fee;
    }

    public static function uncache($uname)
    {
        cache('cache_fee' . $uname, null);
    }
}

class WxHanbj
{

    public static function jsapi($access)
    {
        if (cache('?jsapi')) {
            return cache('jsapi');
        }
        $raw = Curl_Get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $access . '&type=jsapi');
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace($raw);
            return '';
        }
        trace("WxHanbj JsApi " . $res['ticket']);
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
            trace($raw);
            return '';
        }
        trace("WxHanbj TicketApi " . $res['ticket']);
        cache('ticketapi', $res['ticket'], $res['expires_in'] - 10);
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
                trace($msg);
            case 'text':
                $cont = (string)$msg->Content;
                if ($cont === '投票') {
                    $cont = WxOrg::listobj('');
                } elseif (cache('?tempnum' . $cont)) {
                    $cont = cache('tempnum' . $cont);
                    $cont = self::tempid(json_decode($cont, true));
                } else {
                    $cont = '文字信息：' . $cont;
                }
                return self::auto((string)$msg->FromUserName, (string)$msg->ToUserName, $cont);
            case 'image':
            case 'voice':
            case 'video':
            case 'shortvideo':
            case 'location':
            case 'link':
                return self::auto((string)$msg->FromUserName, (string)$msg->ToUserName, $type);
        }
    }

    private static function tempid($data)
    {
        $cont = "临时身份信息验证\n会员编号：{$data['uniq']}\n" .
            "昵称：{$data['nick']}\n" .
            "生成日期：{$data['time']}\n" .
            "生成时间：{$data['time2']}\n" .
            "有效期：30分钟";
        return $cont;
    }

    private static function auto($to, $from, $type)
    {
        trace([
            'TO' => $to,
            'TEXT' => $type
        ]);
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
                    trace($msg);
                }
                return '';
            case 'update_member_card':
                $UserCardCode = (string)$msg->UserCardCode;
                $ModifyBonus = (string)$msg->ModifyBonus;
                trace($UserCardCode . ' --> ' . $ModifyBonus);
                return '';
            default:
                trace($msg);
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
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace($raw);
            return json(['msg' => $raw], 400);
        }
        return true;
    }

    public static function active($code)
    {
        $uname = session('unique_name');
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/membercard/activate?access_token=' . $access;
        $data = [
            "membership_number" => $code,
            "code" => $code,
            "card_id" => config('hanbj_cardid'),
            'init_bonus' => BonusOper::reCalc($uname),
            'init_custom_field_value1' => $uname,
            'init_custom_field_value2' => FeeOper::cache_fee($uname)
        ];
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace($raw);
            return json(['msg' => $raw], 400);
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
        trace($data);
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
    const FEE = 30;
    const ACT = 30;
    const ACT_NAME = '2017秋季仓库整理';
    const _WORKER = [];

    public static function getWorkers()
    {
        return array_merge(self::_WORKER, ['坎丙午', '乾壬申']);//zxyqwe, 魁儿
    }

    public static function reCalc($uname)
    {
        $map['up'] = 1;
        $map['unique_name'] = $uname;
        $act = Db::table('activity')
            ->where($map)
            ->sum('bonus');
        $res = Db::table('nfee')
            ->where($map)
            ->sum('bonus');
        return intval($act) + intval($res);
    }

    private static function up($table, $label)
    {
        $map['up'] = 0;
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left'],
            ['card c', 'c.openid=m.openid', 'left']
        ];
        $item = Db::table($table)
            ->alias('f')
            ->order('f.id')
            ->where($map)
            ->join($join)
            ->field([
                'f.id',
                'm.unique_name',
                'm.openid',
                'm.bonus',
                'c.code',
                'f.bonus as b'
            ])
            ->find();
        if (null != $item) {
            $bonus = intval($item['b']);
            $map['id'] = $item['id'];
            Db::startTrans();
            try {
                $nfee = Db::table($table)
                    ->where($map)
                    ->update(['up' => 1]);
                if ($nfee !== 1) {
                    throw new \Exception('更新事件失败' . json_encode($map));
                }
                $nfee = Db::table('member')
                    ->where(['unique_name' => $item['unique_name']])
                    ->setField('bonus', ['exp', 'bonus+(' . $bonus . ')']);
                if ($nfee !== 1) {
                    throw new \Exception($label . '失败' . json_encode($item));
                }
                Db::commit();
                if ($item['code'] !== null) {
                    $cardup = CardOper::update(
                        $item['unique_name'],
                        $item['code'],
                        $bonus,
                        intval($item['bonus']) + $bonus,
                        $label);
                    if ($cardup !== true) {
                        return $cardup;
                    }
                }
            } catch (\Exception $e) {
                Db::rollback();
                return json(['msg' => '' . $e], 400);
            }
        }
        return json(['msg' => 'ok', 'c' => count($item)]);
    }

    public static function upFee()
    {
        return BonusOper::up('nfee', '会费积分更新');
    }

    public static function upAct()
    {
        return BonusOper::up('activity', '活动积分更新');
    }
}

class OrderOper
{
    const FEE = 1;
    const ACT = 2;
    const FEE_YEAR = [
        ['label' => '续费一年-原价', 'value' => 0, 'fee' => 30],
        ['label' => '续费二年-83折', 'value' => 1, 'fee' => 50],
        ['label' => '续费三年-66折', 'value' => 2, 'fee' => 60]
    ];

    /**
     *
     * @param \app\WxPayUnifiedOrder $input
     * @param int $year
     * @return bool|\app\WxPayUnifiedOrder
     */
    public static function fee($input, $year)
    {
        $fee = OrderOper::FEE_YEAR[$year]['fee'] * 100;
        $label = OrderOper::FEE_YEAR[$year]['label'];
        $openid = session('openid');
        $input->SetBody("会员缴费");
        $input->SetDetail('会员缴费：' . $label);
        $input->SetTotal_fee('' . $fee);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $map['openid'] = $openid;
        $map['fee'] = $fee;
        $map['type'] = OrderOper::FEE;
        $map['value'] = $year;
        $map['trans'] = '';
        $res = Db::table('order')
            ->where($map)
            ->field([
                'outid'
            ])
            ->find();
        if (null === $res) {
            $outid = session('card') . date("YmdHis");
            $map['label'] = $label;
            $map['outid'] = $outid;
            $res = Db::table('order')
                ->insert($map);
            if (1 != $res) {
                return false;
            }
            $input->SetOut_trade_no($outid);
        } else {
            $input->SetOut_trade_no($res['outid']);
        }
        return $input;
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
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace($raw);
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
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace($raw);
        }
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
        $d = date("Y-m-d H:i:s");
        $outid = $data["out_trade_no"];
        $map['outid'] = $outid;
        $map['fee'] = $data['total_fee'];
        $ins['trans'] = $data['transaction_id'];
        $ins['time'] = $d;
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        Db::startTrans();
        try {
            $res = Db::table('order')
                ->alias('f')
                ->where($map)
                ->join($join)
                ->field([
                    'f.type',
                    'f.value',
                    'f.label',
                    'm.unique_name',
                    'm.openid'
                ])
                ->find();
            if (null === $res) {
                throw new Exception(json_encode($map));
            }
            $map['trans'] = '';
            $up = Db::table('order')
                ->where($map)
                ->data($ins)
                ->update();
            if ($up === 0) {
                Db::rollback();
                return true;
            }
            if ('1' === $res['type']) {
                $this->handleFee($res['value'], $res['unique_name'], $data['transaction_id'], $d);
                Db::commit();
                WxTemp::notifyFee($res['openid'],
                    $res['unique_name'],
                    intval($data['total_fee']) / 100,
                    FeeOper::cache_fee($res['unique_name']),
                    $res['label']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            trace('' . $e);
            return false;
        }
        return true;
    }

    private function handleFee($value, $uname, $trans, $d)
    {
        $value = intval($value) + 1;
        $ins = [];
        $oper = 'Weixin_' . substr($trans, strlen($trans) - 6);
        while (count($ins) < $value) {
            $ins[] = [
                'unique_name' => $uname,
                'oper' => $oper,
                'code' => 1,
                'fee_time' => $d,
                'bonus' => BonusOper::FEE
            ];
        }
        $up = Db::table('nfee')
            ->insertAll($ins);
        FeeOper::uncache($uname);
        if ($up != $value) {
            throw new Exception('nfee ' . $value);
        }
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

class WxOrg
{
    const top = [];
    const vice = [];
    const leader = [];
    const member = [];
    const obj = ['素问', '采峦'];
    const name = '2017???';
    const test = [];

    public static function getAll()
    {
        return array_merge(self::top, self::vice, self::leader, self::member, ['坎丙午']);
    }

    public static function getUpper()
    {
        return array_merge(self::top, self::vice, ['坎丙午']);
    }

    public static function getLower()
    {
        return array_merge(self::leader, self::member, ['坎丙午']);
    }

    public static function listobj($uname)
    {
        $ret = "有以下投票\n";
        foreach (self::obj as $item) {
            $ret .= '<a href="https://app.zxyqwe.com/hanbj/wxtest/obj/' . $item . '">' . $item . "</a>\n";
        }
        return $ret;
    }
}