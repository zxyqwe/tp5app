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
use think\exception\HttpResponseException;

class MemberOper
{
    /*
     * id, !!unique_name, code, bonus 自动
     * !!tieba_id year_time !!openid人写
     * gender, phone, QQ, master, eid, rn, mail 人工后台
     * pref, web_name 随便
     * */
    const UNUSED = -1;
    const NORMAL = 0;
    const BANNED = 1;
    const FREEZE = 2;
    const TEMPUSE = 3;
    const JUNIOR = 4;
    const CYCLE = [
        "甲子", "乙丑", "丙寅", "丁卯", "戊辰", "己巳", "庚午", "辛未", "壬申", "癸酉",
        "甲戌", "乙亥", "丙子", "丁丑", "戊寅", "己卯", "庚辰", "辛巳", "壬午", "癸未",
        "甲申", "乙酉", "丙戌", "丁亥", "戊子", "己丑", "庚寅", "辛卯", "壬辰", "癸巳",
        "甲午", "乙未", "丙申", "丁酉", "戊戌", "己亥", "庚子", "辛丑", "壬寅", "癸卯",
        "甲辰", "乙巳", "丙午", "丁未", "戊申", "己酉", "庚戌", "辛亥", "壬子", "癸丑",
        "甲寅", "乙卯", "丙辰", "丁巳", "戊午", "己未", "庚申", "辛酉", "壬戌", "癸亥"
    ];
    const GROUP = ["乾", "坤", "坎", "离", "震", "巽", "艮", "兑", "夏"];

    public static function getMember()
    {
        return [self::JUNIOR, self::NORMAL];
    }

    public static function trans($v)
    {
        switch ($v) {
            case self::NORMAL:
                return '实名会员';
            case self::UNUSED:
                return '<span class="temp-text">空号</span>';
            case self::BANNED:
                return '<span class="temp-text">注销</span>';
            case self::FREEZE:
                return '<span class="temp-text">停机保号</span>';
            case self::TEMPUSE:
                return '<span class="temp-text">临时抢号</span>';
            case self::JUNIOR:
                return '初级会员';
            default:
                return '<span class="temp-text">异常：' . $v . '</span>';
        }
    }

    public static function create_unique_unused()
    {
        $unique = [];
        foreach (self::GROUP as $x) {
            foreach (self::CYCLE as $y) {
                $unique[] = "$x$y";
            }
        }
        $map['unique_name'] = ['in', $unique];
        $ret = Db::table('member')
            ->where($map)
            ->field('unique_name  as u')
            ->select();
        $already = [];
        foreach ($ret as $i) {
            $already[] = $i['u'];
        }
        $unique = array_diff($unique, $already);
        if (count($unique) == 0) {
            return ['g' => [], 'r' => 0, 'l' => 0];
        }
        $data = [];
        foreach ($unique as $u) {
            $data[] = [
                'unique_name' => $u,
                'tieba_id' => $u,
                'code' => self::UNUSED
            ];
        }
        $ret = Db::table('member')
            ->insertAll($data);
        return ['g' => $unique, 'r' => $ret, 'l' => count($unique)];
    }

    public static function list_code($c)
    {
        $map['code'] = $c;
        $ret = Db::table('member')
            ->where($map)
            ->field('unique_name  as u')
            ->select();
        $already = [];
        foreach ($ret as $i) {
            $already[] = $i['u'];
        }
        trace("list_code $c " . count($already));
        return $already;
    }

    public static function daily()
    {
        $name = "MemberOper::daily()";
        if (cache("?$name")) {
            return;
        }
        cache($name, $name, 86300);
        $ret = self::list_code(self::TEMPUSE);
        foreach ($ret as $i) {
            $t = self::Temp2Junior($i);
            if (!$t) {
                self::Temp2Unused($i);
            }
        }
        $ret = self::list_code(self::JUNIOR);
        foreach ($ret as $i) {
            self::Junior2Temp($i);
        }
        $ret = self::list_code(self::BANNED);
        foreach ($ret as $i) {
            self::Banned2Normal($i);
        }
        $ret = self::list_code(self::NORMAL);
        foreach ($ret as $i) {
            self::Normal2Banned($i);
        }
    }

    public static function Unused2Temp($unique_name, $tieba_id, $openid)
    {
        $ca = "Unused2Temp$unique_name";
        $map['code'] = self::UNUSED;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::TEMPUSE;
        $data['tieba_id'] = $tieba_id;
        $data['year_time'] = date('Y');
        $data['openid'] = $openid;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            if ($ret === 1) {
                cache($ca, 2 * 86400);
            }
            trace("$unique_name UNUSED TEMPUSE $ret");
            CardOper::common($unique_name, '临时抢号');
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Unused2Temp $unique_name $e");
            if (false !== strpos($e, 'Duplicate')) {
                $e = '名称重复';
            }
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function Temp2Unused($unique_name)
    {
        $ca = "?Unused2Temp$unique_name";
        if (cache($ca)) {
            return false;
        }
        if (!FeeOper::owe($unique_name)) {
            return false;
        }
        $map['code'] = self::TEMPUSE;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::UNUSED;
        $data['tieba_id'] = $unique_name;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name TEMPUSE UNUSED $ret");
            FeeOper::clear($unique_name);
            CardOper::unuesd($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Temp2Unused $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function Temp2Junior($unique_name)
    {
        if (FeeOper::owe($unique_name)) {
            return false;
        }
        $map['code'] = self::TEMPUSE;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::JUNIOR;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name TEMPUSE JUNIOR $ret");
            CardOper::common($unique_name, '初级会员');
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Temp2Junior $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function Junior2Temp($unique_name)
    {
        if (!FeeOper::owe($unique_name, -1)) {
            return false;
        }
        $map['code'] = self::JUNIOR;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::TEMPUSE;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name JUNIOR TEMPUSE $ret");
            CardOper::common($unique_name, '临时抢号');
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Junior2Temp $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    public static function Junior2Normal($unique_name, $tieba_id, $gender, $phone, $QQ, $master, $eid, $rn, $mail)
    {
        if (FeeOper::owe($unique_name)) {
            return false;
        }
        $map['code'] = self::JUNIOR;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::NORMAL;
        $data['tieba_id'] = $tieba_id;
        $data['gender'] = $gender;
        $data['phone'] = $phone;
        $data['QQ'] = $QQ;
        $data['master'] = $master;
        $data['eid'] = $eid;
        $data['rn'] = $rn;
        $data['mail'] = $mail;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name JUNIOR NORMAL $ret");
            trace(json_encode($data));
            CardOper::common($unique_name, '实名会员');
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Junior2Normal $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function Normal2Freeze($unique_name)
    {
        $map['code'] = self::NORMAL;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::FREEZE;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name NORMAL FREEZE $ret");
            CardOper::freeze($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Normal2Freeze $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function Freeze2Normal($unique_name)
    {
        $map['code'] = self::FREEZE;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::NORMAL;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name FREEZE NORMAL $ret");
            CardOper::common($unique_name, '实名会员');
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Freeze2Normal $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function Normal2Banned($unique_name)
    {
        if (!FeeOper::owe($unique_name, -2)) {
            return false;
        }
        $map['code'] = self::NORMAL;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::BANNED;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name NORMAL BANNED $ret");
            CardOper::banned($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Normal2Banned $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function Banned2Normal($unique_name)
    {
        if (FeeOper::owe($unique_name, 2)) {
            return false;
        }
        $map['code'] = self::BANNED;
        $map['unique_name'] = $unique_name;
        $data['code'] = self::NORMAL;
        $data['bonus'] = 0;
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            trace("$unique_name BANNED NORMAL $ret");
            CardOper::common($unique_name, '实名会员');
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Banned2Normal $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }
}

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
        if (null === $res) {
            return 0;
        }
        $year = Db::table('member')
            ->where($map)
            ->value('year_time');
        $fee = intval($year) + intval($res['n']) - 1;
        cache($cache_name, $fee);
        return $fee;
    }

    public static function owe($uname, $off = 0)
    {
        return self::cache_fee($uname) < intval(date('Y')) + $off;
    }

    public static function clear($uname)
    {
        trace("Fee Clear $uname");
        $map['unique_name'] = $uname;
        $data['unique_name'] = $uname . date("Y-m-d H:i:s");
        Db::table('nfee')
            ->where($map)
            ->update($data);
        self::uncache($uname);
    }

    public static function uncache($uname)
    {
        cache('cache_fee' . $uname, null);
    }
}

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
        $from = (string)$msg->FromUserName;
        $to = (string)$msg->ToUserName;
        switch ($type) {
            case 'event':
                return self::do_event($msg);
            default:
                trace($msg);
            case 'text':
                $cont = (string)$msg->Content;
                if ($cont === '投票') {
                    $org = new WxOrg();
                    $cont = $org->listobj($from);
                    return self::auto($from, $to, $cont, false, '投票');
                } elseif (cache('?tempnum' . $cont)) {
                    $cont = cache('tempnum' . $cont);
                    $cont = self::tempid(json_decode($cont, true));
                    return self::auto($from, $to, $cont, false, '临时身份');
                }
                $cont = '文字信息：' . $cont;
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
        $cont = "临时身份信息验证\n会员编号：{$data['uniq']}\n" .
            "昵称：{$data['nick']}\n" .
            "生成日期：{$data['time']}\n" .
            "生成时间：{$data['time2']}\n" .
            "有效期：30分钟";
        return $cont;
    }

    private static function auto($to, $from, $type, $debug = true, $debug_msg = '')
    {
        if ($debug) {
            trace([
                'TO' => $to,
                'TEXT' => $type
            ]);
        } else {
            trace($to . ' ' . $debug_msg);
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
            }
        }
        return view('jump');
    }
}

class CardOper
{
    /*
{
    "card": {
        "card_type": "MEMBER_CARD",
        "member_card": {
            "background_pic_url": "http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJel0W2MjUQgaA4tiaQuK4kkoToThoHkPumpNHvicf19vPMLMBLGY6VnDmgMWMwmck48hR1ib8EOjO6LQ/0",
            "base_info": {
                "logo_url": "http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJcuGbV0gs8GC1jHSYJ7xWgkpoN9T5icr1DwmTeTicXgfTibjYiazmJLv8MAUBHXZLXtSicopribicOT8mYNw/0",
                "brand_name": "北京汉服协会",
                "code_type": "CODE_TYPE_QRCODE",
                "title": "会员卡",
                "color": "Color010",
                "description":
                "1、会员卡一经售出，不退不换，请谨慎决策。\n2、购买会员卡默认已了解并承诺遵守会员须知中的有关规定。\n3、积分有效期为2年。\n4、汉服北京有权对会员福利做出适当调整，不另行通知。",
                "date_info": {
                    "type": "DATE_TYPE_PERMANENT",
                },
                "sku": {
                    "quantity": 10000000
                },
                "get_limit": 1,
                "use_custom_code": False,
                "can_give_friend": False,
                "custom_url_name": "活动报名",
                "custom_url": "https://app.zxyqwe.com/hanbj/mobile/#activity",
                "custom_url_sub_title": "更多惊喜",
                "promotion_url_name": "会员特权",
                "promotion_url": "https://app.zxyqwe.com/hanbj/mobile/#promotion",
                "promotion_url_sub_title": "福利多多"
            },
            "advanced_info": {
                "text_image_list": [
                    {
                        "image_url": "http://mmbiz.qpic.cn/mmbiz/p98FjXy8LacgHxp3sJ3vn97bGLz0ib0Sfz1bjiaoOYA027iasqSG0sjpiby4vce3AtaPu6cIhBHkt6IjlkY9YnDsfw/0",
                        "text":
                        "1、会员须遵守国家法律，遵守汉服北京的各项章程、规定，认同本群体的原则理念和基本共识，履行吧务组决议，不公开发表过激或极端言论，不做有损汉服群体整体利益和对外形象的行为。因违法违纪、违反汉服北京规章制度等行为造成恶劣影响，可由汉服北京管理团队讨论后，终止或撤销其会员资格，所缴纳会费恕不退回。\n2、会员在行使任何权利时，都应携带并配合出示有效会员凭证，否则将不享有会员权利。\n3、会员可主动申请终止或撤销会员资格，自汉服北京管理团队确认之日起会员资格失效，所缴纳会费恕不退回；再次申请加入会员时，重新计算会员时效。"
                    }
                ]
            },
            "supply_bonus": True,
            "bonus_url": "https://app.zxyqwe.com/hanbj/mobile/#bonus",
            "supply_balance": False,
            "prerogative":
            "1、公开活动费用减免。吧务组组织的面向大众的活动，包括但不限于传统节日大活动、雅集类活动等。\n2、特别活动优先参与。对于部分限定活动享有优先报名特权。\n3、汉北周边购买最高折扣。尊享汉北周边产品最高折扣价。汉服北京承诺同一时间在任何渠道所售周边产品价格不低于会员折扣价。\n4、汉服租借及礼仪类服务折扣。可以优惠价租借汉服，预定成人礼抓周礼等礼仪类服务享受专属折扣。（限指定公司）\n5、汉北合作商家优惠。在指定汉服、饰品、国货、汉婚及其他商家购买产品和服务尊享汉北会员折扣。\n6、会员积分。参与活动可获得积分，达到一定数额可升级高级会员或兑换其他福利。\n7、参与限量会员牌订制。\n8、其他不定期其他特殊优惠。",
            "auto_activate": False,
            "activate_url": "https://app.zxyqwe.com/hanbj/mobile",
            "custom_field1": {
                "name": "会员号",
                "url": "https://app.zxyqwe.com/hanbj/mobile",
            },
            "custom_field2": {
                "name": "缴费",
                "url": "https://app.zxyqwe.com/hanbj/mobile/#valid",
            },
            "custom_cell1": {
                "name": "未完成",
                "tips": "显示未完成",
                "url": "https://app.zxyqwe.com/hanbj/mobile/#custom",
            }
        }
    }

}
    */
    private static function U2Card($uname)
    {
        $map['m.unique_name'] = $uname;
        $join = [
            ['card c', 'm.openid=c.openid', 'left']
        ];
        $ret = Db::table('member')
            ->alias('m')
            ->where($map)
            ->join($join)
            ->field('c.code')
            ->find();
        if (null === $ret) {
            return null;
        }
        return $ret['code'];
    }

    public static function banned($uname)
    {
        $code = self::U2Card($uname);
        if (null === $code) {
            return;
        }
        self::update('注销', $code, 0, 0, '注销');
    }

    public static function freeze($uname)
    {
        $code = self::U2Card($uname);
        if (null === $code) {
            return;
        }
        self::update('停机', $code, 0, 0, '停机');
    }

    public static function unuesd($uname)
    {
        $code = self::U2Card($uname);
        if (null === $code) {
            return;
        }
        self::update('未选择', $code, 0, 0, '未选择');
    }

    public static function common($uname, $msg)
    {
        $code = self::U2Card($uname);
        if (null === $code) {
            return;
        }
        self::update($uname, $code, 0, 0, "激活为：$msg");
    }

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
            'custom_field_value1' => $uni,
            'custom_field_value2' => FeeOper::cache_fee($uni),
            "notify_optional" => [
                "is_notify_bonus" => true,
                "is_notify_custom_field1" => true,
                "is_notify_custom_field2" => true
            ]
        ];
        $raw = Curl_Post($data, $url, false);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace($raw);
            throw new HttpResponseException(json(['msg' => $raw], 400));
        }
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
    const ACT = 10;
    const VOLUNTEER = 10;
    const ACT_NAME = '2017冬季团建';
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

    public static function up($table, $label)
    {
        $map['up'] = 0;
        $map['m.code'] = ['in', MemberOper::getMember()];
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
                'm.bonus',
                'c.code',
                'f.bonus as b'
            ])
            ->find();
        if (null === $item) {
            return json(['msg' => 'ok', 'c' => 0]);
        }
        $bonus = intval($item['b']);
        $map['id'] = $item['id'];
        unset($map['m.code']);
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
                CardOper::update(
                    $item['unique_name'],
                    $item['code'],
                    $bonus,
                    intval($item['bonus']) + $bonus,
                    $label);
            } else {
                trace("{$item['unique_name']} 没有会员卡");
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['msg' => '' . $e], 400);
        }
        return json(['msg' => 'ok', 'c' => 1]);
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
        $outid = $data["out_trade_no"];
        if (!$this->Queryorder($outid)) {
            return false;
        }
        $total_fee = $data['total_fee'];
        $transaction_id = $data['transaction_id'];
        $d = date("Y-m-d H:i:s");
        $map['outid'] = $outid;
        $map['fee'] = $total_fee;
        $ins['trans'] = $transaction_id;
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
                trace('重来订单 ' . json_encode($data));
                return true;
            }
            if ('1' === $res['type']) {
                $this->handleFee($res['value'], $res['unique_name'], $transaction_id, $d);
                Db::commit();
                WxTemp::notifyFee($res['openid'],
                    $res['unique_name'],
                    intval($total_fee) / 100,
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

class LogUtil
{
    public static function list_dir($dir, $name)
    {
        $result = ['text' => $name];
        $cdir = scandir($dir, SCANDIR_SORT_DESCENDING);
        if (!empty($cdir)) {
            $result['nodes'] = [];
        }
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result['nodes'][] = self::list_dir($dir . DIRECTORY_SEPARATOR . $value, $value);
                } else {
                    $result['nodes'][] = ['text' => explode('.', $value)[0]];
                }
            }
        }
        return $result;
    }
}

class WxOrg
{
    const year = 12;
    const name = '2017会长层测评';
    const test = [
        [
            'q' => '你对A会长分管部门（无具体分管的，则评价各个部门）工作情况评价（40分）'
        ],
        [
            'q' => '任务完成及实际效果（10分）',
            'a' => [
                '超额完成工作任务，效果完美，意义影响大（10分）',
                '完成所有工作任务，效果有积极作用（8-9分）',
                '基本完成工作任务，工作效果平平（6-7分）',
                '有明显未完成的任务，且没有替代性工作；或工作效果差，甚至有负面影响（5分及以下）'
            ]
        ],
        [
            'q' => '团队管理与协作（10分）',
            'a' => [
                '内部分工清晰科学、配合顺畅，凝聚力很强10分）',
                '有一定的分工协作，具有团队意识（8-9分）',
                '成员勤怠程度参差不齐，勉强算是团队（6-7分）',
                '几乎无分工配合，整体散漫懈怠（5分及以下）'
            ]
        ],
        [
            'q' => '人才培养与个人纪律（10分）',
            'a' => [
                '骨干很好地带动教导干事，成员很好地履行参会、活动等义务（10分）',
                '骨干做给干事看，有心人能有所提高，成员基本能守纪律（8-9分）',
                '骨干爱单干，干事难以提高，有不守纪律情况发生（6-7分）',
                '完全不管干事培养问题，成员违纪情况时有发生。（5分及以下）'
            ]
        ],
        [
            'q' => '工作创新和团队进取心（10分）',
            'a' => [
                '团队思维活跃，工作上各种推陈出新（10分）',
                '不满足于按部就班，能作出一些新业绩（8-9分）',
                '基本按照固有套路工作，偶有创新改进（6-7分）',
                '无进取心和创新意识，应付工作（5分及以下）'
            ]
        ],
        [
            'q' => '你对A会长个人工作状态的评价（60分）'
        ], [
            'q' => '履职尽责（本题与其他题目不同，按照分支问题给分即可，共20分）',
            'a' => [
                '带头积极参加全年会议与活动（6分）',
                '主动协调、指导和配合会务或活动组织（4分）',
                '遵守会议纪律和活动纪律（5分）',
                '及时批复签报，参与或作出决策（5分）'
            ],
            's' => 20
        ],
        [
            'q' => '工作态度（10分）',
            'a' => [
                '任劳任怨，用于担当，竭尽所能完成工作。10分',
                '有责任心，能较好完成分内工作。8-9分',
                '需要提醒督促勉强完成工作。6-7分',
                '敷衍了事,态度傲慢，无责任心，粗心、拖拉。5分及以下'
            ]
        ],
        [
            'q' => '领导能力（10分）',
            'a' => [
                '善于分配工作，积极传授工作知识技能，引导下属完成任务。10分',
                '能够较好的分配工作，有效传授工作知识技能，指导下属完成工作任务。8-9分',
                '本能够合理地分配工作，具备一定指导下属工作的能力。6-7分',
                '欠缺分配工作及指导下属的工作方法，工作任务完成偶有困难。5分及以下'
            ]
        ],
        [
            'q' => '团队意识（10分）',
            'a' => [
                '工作有计划，与其他会长和分管部门配合无间，工作完成顺利。10分',
                '爱护团体，常主动帮助指导别人。8-9分',
                '肯应他人要求帮助别人。6-7分',
                '自由散漫，蛮横不妥协，难以合作。5分及以下'
            ]
        ],
        [
            'q' => '创新意识（10分）',
            'a' => [
                '创新能力和大局观强，在工作改善方面，常提出建设性意见并采纳。10分',
                '有一定创新意识和能力，有时在工作方法上有改进。8-9分',
                '创新意识一般，偶尔有改进建议，能完成任务。6-7分',
                '毫无创新，只能基本完成任务；5分及以下'
            ]
        ]
    ];

    function __construct()
    {
        $map['year'] = self::year;
        $map['grade'] = ['<=', '3'];
        $ret = Db::table('fame')
            ->where($map)
            ->field([
                'unique_name as u',
                'grade as g'
            ])
            ->select();
        $upper = [];
        $lower = [];
        $obj = [];
        foreach ($ret as $item) {
            switch ($item['g']) {
                case 0:
                case 1:
                    $obj[] = $item['u'];
                    $upper[] = $item['u'];
                    break;
                case 2:
                    $upper[] = $item['u'];
                    break;
                case 3:
                    $lower[] = $item['u'];
                    break;
                default:
                    trace(json_encode($item));
            }
        }
        $this->upper = $upper;
        $this->lower = $lower;
        $this->obj = $obj;
    }

    public function getAll()
    {
        return array_merge($this->upper, $this->lower);
    }

    public function getUser()
    {
        return array_merge($this->getAll(), ['坎丙午']);
    }

    public function getAns()
    {
        $user = $this->getAll();
        $data = [];
        foreach ($user as $u) {
            foreach ($this->obj as $o) {
                $c_name = $u . $o . WxOrg::name;
                if (cache('?' . $c_name)) {
                    $data[] = [
                        'ans' => json_decode(cache($c_name), true),
                        'u' => $u,
                        'o' => $o,
                        'w' => in_array($u, $this->upper) ? 2.0 : 1.0
                    ];
                }
            }
        }
        return $data;
    }

    public function getAvg($data)
    {
        $cnt = [];
        foreach ($this->obj as $o) {
            $cnt[$o] = 0;
        }
        $ans = [];
        foreach ($data as $item) {
            $weight = $item['w'];
            $cnt[$item['o']] += $weight;
            for ($i = 0; $i < count(self::test); $i++) {
                if (!isset($ans[$item['o']])) {
                    $ans[$item['o']] = [];
                }
                if (!isset($ans[$item['o']][$i])) {
                    $ans[$item['o']][$i] = 0;
                }
                $ans[$item['o']][$i] += $weight * intval($item['ans']['sel'][$i]);
            }
        }

        $ret = [];
        for ($i = 0; $i < count(self::test); $i++) {
            $test = self::test[$i];
            if (!isset($test['a'])) {
                continue;
            }
            $tmp = ['q' => $test['q']];
            foreach ($this->obj as $o) {
                if (isset($ans[$o])) {
                    $ans[$o][$i] /= $cnt[$o];
                    $tmp[$o] = $ans[$o][$i];
                }
            }
            $ret[] = $tmp;
        }
        $tmp = ['q' => '总分（100分）'];
        foreach ($this->obj as $o) {
            if (isset($ans[$o])) {
                $tmp[$o] = array_sum($ans[$o]);
            }
        }
        $ret[] = $tmp;
        return $ret;
    }

    public function getComment($data)
    {
        $ret = [];
        foreach ($data as $item) {
            if (!isset($item['ans']['sel_add'])) {
                $item['ans']['sel_add'] = [];
            }
            $cmt = $item['ans']['sel_add'];
            $sel = $item['ans']['sel'];
            for ($i = 0; $i < count($cmt); $i++) {
                if (!empty($cmt[$i])) {
                    $ret[] = [
                        'q' => self::test[$i]['q'],
                        'o' => $item['o'],
                        't' => $cmt[$i],
                        's' => $sel[$i]
                    ];
                }
            }
        }
        return $ret;
    }

    private function progress()
    {
        $all = $this->getAll();
        $len = count($all) * count($this->obj);
        $acc = 0;
        foreach ($this->obj as $obj) {
            foreach ($all as $item) {
                $c_name = $item . $obj . self::name;
                if (cache('?' . $c_name)) {
                    $acc++;
                }
            }
        }
        if ($acc !== $len) {
            $res = $acc * 100.0 / $len;
            return self::name . "\n投票数量：$acc / $len\n总进度：" . round($res, 2) . "%\n";
        } else {
            return false;
        }
    }

    public static function checkAns(&$ans)
    {
        if (!is_array($ans)) {
            throw new HttpResponseException(json(['msg' => 'array'], 400));
        }
        $len = count(self::test);
        if (!isset($ans['sel']) || !is_array($ans['sel']) || count($ans['sel']) !== $len) {
            throw new HttpResponseException(json(['msg' => 'sel'], 400));
        }
        if (array_sum($ans['sel']) === 100) {
            throw new HttpResponseException(json(['msg' => '满分'], 400));
        }
        if (!isset($ans['sel_add'])) {
            $ans['sel_add'] = [];
        }
        if (!is_array($ans['sel_add'])) {
            throw new HttpResponseException(json(['msg' => 'sel_add'], 400));
        }
        foreach (range(0, $len - 1) as $i) {
            $tmp = self::test[$i];
            if (!isset($tmp['a'])) {
                continue;
            }
            $s = 10;
            if (isset($tmp['s'])) {
                $s = $tmp['s'];
            }
            $s *= 0.6;
            if ($ans['sel'][$i] < $s
                && (!isset($ans['sel_add'][$i]) || count($ans['sel_add'][$i]) < 15)
            ) {
                throw new HttpResponseException(json(['msg' => 'sel ' . $i], 400));
            }
        }
    }

    private function all_done()
    {
        return '已完成';
    }

    public function listobj($from)
    {
        $map['openid'] = $from;
        $res = Db::table('member')
            ->alias('m')
            ->where($map)
            ->field('unique_name')
            ->find();
        $uname = $res['unique_name'];
        if (!in_array($uname, $this->getUser())) {
            return '文字信息：投票';
        }
        $prog = $this->progress();
        if (false === $prog) {
            return $this->all_done();
        }
        $ret = "有以下投票，二十分钟有效\n" . $prog;
        $finish = "-----\n";
        $unfinish = "-----\n";
        foreach ($this->obj as $item) {
            $c_name = $uname . $item . self::name;
            $nonce = WxHanbj::setJump('wxtest', $item, $uname, 60 * 20);
            if (!cache('?' . $c_name)) {
                $unfinish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">$item</a>\n";
            } else {
                $finish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">已完成-$item</a>\n";
            }
        }
        return $ret . $unfinish . $finish;
    }
}
