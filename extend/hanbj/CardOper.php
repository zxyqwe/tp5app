<?php

namespace hanbj;


use PDOStatement;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\exception\PDOException;
use think\Model;
use think\response\Json;
use util\MysqlLog;

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
                "promotion_url_name": "商家优惠",
                "promotion_url": "https://app.zxyqwe.com/hanbj/mobile/#prom",
                "promotion_url_sub_title": "福利多多"
            },
            "advanced_info": {
                "text_image_list": [
                    {
                        "image_url": "http://mmbiz.qpic.cn/mmbiz_jpg/PP2q8S7QAJdkZWIvIQheoscaVOPSibtibZQUqibrjNzZoIqo5mLeib1RhUe8vvjhf9LWdBhX4qV1mc7iaKInIYlWTqg/0",
                        "text":
                        "1、会员须遵守国家法律，遵守汉服北京的各项章程、规定，认同本群体的原则理念和基本共识，履行吧务组决议，不公开发表过激或极端言论，不做有损汉服群体整体利益和对外形象的行为。因违法违纪、违反汉服北京规章制度等行为造成恶劣影响，可由汉服北京管理团队讨论后，终止或撤销其会员资格，所缴纳会费恕不退回。\n2、会员在行使任何权利时，都应携带并配合出示有效会员凭证，否则将不享有会员权利。\n3、会员可主动申请终止或撤销会员资格，自汉服北京管理团队确认之日起会员资格失效，所缴纳会费恕不退回；再次申请加入会员时，重新计算会员时效。"
                    }
                ]
            },
            "supply_bonus": True,
            "bonus_url": "https://app.zxyqwe.com/hanbj/mobile/#bonus",
            "supply_balance": False,
            "prerogative":
          ToDo  "1、公开活动费用减免。吧务组组织的面向大众的活动，包括但不限于传统节日大活动、雅集类活动等。\n2、特别活动优先参与。对于部分限定活动享有优先报名特权。\n3、汉北周边购买最高折扣。尊享汉北周边产品最高折扣价。汉服北京承诺同一时间在任何渠道所售周边产品价格不低于会员折扣价。\n4、汉服租借及礼仪类服务折扣。可以优惠价租借汉服，预定成人礼抓周礼等礼仪类服务享受专属折扣。（限指定公司）\n5、汉北合作商家优惠。在指定汉服、饰品、国货、汉婚及其他商家购买产品和服务尊享汉北会员折扣。\n6、会员积分。参与活动可获得积分，达到一定数额可升级高级会员或兑换其他福利。\n7、参与限量会员牌订制。\n8、其他不定期其他特殊优惠。",
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
                "name": "汉北会员",
                "tips": "攻略指南",
                "url": "https://app.zxyqwe.com/hanbj/mobile/help",
            }
        }
    }

}
    */

    /**
     * @param $uname
     * @return array|false|PDOStatement|string|Model|null
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
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
            ->field(['c.code', 'm.code as c'])
            ->find();
        if (null === $ret) {
            return null;
        }
        return $ret;
    }

    /**
     * @param $openid
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function clear($openid)
    {
        $res = Db::table('card')
            ->where(['openid' => $openid])
            ->field(['code'])
            ->find();
        if (null === $res) {
            trace("card clear openid $openid", MysqlLog::ERROR);
            return;
        }
        self::update('未选择', $res['code'], '未选择');
    }

    /**
     * @param $code
     * @return array|false|PDOStatement|string|Model
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function Card2U($code)
    {
        $map['f.code'] = $code;
        $map['m.code'] = ['in', MemberOper::getMember()];
        $map['card_id'] = config('hanbj_cardid');
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $res = Db::table('card')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->field([
                'm.unique_name',
                'm.openid'
            ])
            ->find();
        if (null === $res) {
            throw new HttpResponseException(json(['msg' => '查无此人：' . $code], 400));
        }
        return $res;
    }

    /**
     * @param $uname
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function renew($uname)
    {
        $ret = self::U2Card($uname);
        if (null === $ret) {
            return;
        }
        $code = $ret['code'];
        if (null === $code) {
            return;
        }
        switch ($ret['c']) {
            case MemberOper::BANNED:
                self::update('注销', $code, '注销');
                break;
            case MemberOper::FREEZE:
                self::update('停机', $code, '停机');
                break;
            case MemberOper::UNUSED:
            case MemberOper::DELETED_HISTORY:
                self::update('未选择', $code, '未选择');
                break;
            case MemberOper::TEMPUSE:
                self::update("临时抢号", $code, "临时抢号");
                break;
            case MemberOper::JUNIOR:
                $bonus = BonusOper::reCalc($uname);
                self::update($uname, $code, "激活为：会员", $bonus, $bonus);
                break;
            case MemberOper::NORMAL:
                $bonus = BonusOper::reCalc($uname);
                self::update($uname, $code, "激活为：实名会员", $bonus, $bonus);
                break;
            default:
                trace("激活为：{$uname} {$code} {$ret['c']}", MysqlLog::ERROR);
                self::update($uname, $code, "激活为：{$ret['c']}");
        }
    }

    public static function update($uni, $card, $msg, $add_b = 1, $b = 0)
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
            'custom_field_value2' => FeeOper::cache_fee($uni)->format('Y'),
            "notify_optional" => [
                "is_notify_bonus" => false,
                "is_notify_custom_field1" => true,
                "is_notify_custom_field2" => true
            ]
        ];
        $raw = Curl_Post($data, $url, false, 60);
        $res = json_decode($raw, true);
        $log = implode('; ', [$uni, $card, $msg, $add_b, $b]);
        if ($res['errcode'] !== 0) {
            trace("update $log $raw " . json_encode($data), MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $raw], 400));
        } else {
            trace($log, MysqlLog::INFO);
        }
    }

    /**
     * @param $code
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function active($code)
    {
        $uname = session('unique_name');
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $url = 'https://api.weixin.qq.com/card/membercard/activate?access_token=' . $access;
        $data = [
            "membership_number" => $code,
            "code" => $code,
            "card_id" => config('hanbj_cardid'),
            'init_bonus' => 1,
            'init_custom_field_value1' => '激活中',
            'init_custom_field_value2' => FeeOper::cache_fee($uname)->format('Y')
        ];
        $raw = Curl_Post($data, $url, false, 60);
        $res = json_decode($raw, true);
        if ($res['errcode'] !== 0) {
            trace("active $raw " . json_encode($data), MysqlLog::ERROR);
            return json(['msg' => $raw], 400);
        }
        $map['status'] = 0;
        $map['code'] = $code;
        $map['openid'] = session('openid');
        $map['card_id'] = config('hanbj_cardid');
        $res = Db::table('card')
            ->where($map)
            ->setField('status', 1);
        if ($res !== 1) {
            trace("active " . json_encode($data), MysqlLog::ERROR);
            return json(['msg' => '更新失败'], 500);
        }
        self::renew($uname);
        return json(['msg' => 'OK']);
    }

    /**
     * @param $msg
     * @return string
     * @throws Exception
     * @throws PDOException
     */
    public static function del_card($msg)
    {
        $cardid = (string)$msg->UserCardCode;
        $openid = (string)$msg->FromUserName;
        $data = [
            'openid' => $openid,
            'code' => $cardid,
            'card_id' => config('hanbj_cardid')
        ];
        $res = Db::table('card')
            ->where($data)
            ->delete();
        if ($res !== 1) {
            $data['status'] = 'del fail';
        } else {
            $data['status'] = 'del OK';
        }
        trace(json_encode($data), MysqlLog::INFO);
        return '';
    }

    public static function get_card($msg)
    {
        $cardid = (string)$msg->UserCardCode;
        $openid = (string)$msg->FromUserName;
        $data = [
            'openid' => $openid,
            'code' => $cardid,
            'card_id' => config('hanbj_cardid')
        ];
        $res = Db::table('card')
            ->insert($data);
        if ($res !== 1) {
            $data['status'] = 'get fail';
        } else {
            $data['status'] = 'get OK';
        }
        trace(json_encode($data), MysqlLog::INFO);
        return '';
    }

    /**
     * @param $map
     * @return int|mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function mod_ret($map)
    {
        $map['card_id'] = config('hanbj_cardid');
        $card = Db::table('card')
            ->where($map)
            ->field([
                'status',
                'code'
            ])
            ->find();
        if ($card === null) {
            return -1;
        }
        session('card', $card['code']);
        return $card['status'];
    }
}
