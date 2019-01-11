<?php

namespace hanbj;

use hanbj\weixin\WxHanbj;
use think\Db;
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
        "甲寅", "乙卯", "丙辰", "丁巳", "戊午", "己未", "庚申", "辛酉", "壬戌", "癸亥",
    ];
    const GROUP = ["乾", "坤", "坎", "离", "震", "巽", "艮", "兑", "夏", "商", "周", "秦", "汉", "晋", "隋", "唐", "宋"];
    const VERSION = 'wx_succ_2';

    public static function wx_login()
    {
        if (self::VERSION !== session('wx_login')) {
            session(null);
        } else {
            return true;
        }
        if (input('?get.code')) {
            $api = config('hanbj_api');
            $sec = config('hanbj_secret');
            $openid = WX_code(input('get.code'), $api, $sec);
            if (is_string($openid)) {
                session('openid', $openid);
                session('unique_name', $openid);
                session('wx_login', self::VERSION);
                return true;
            }
        }
        return false;
    }

    public static function getMember()
    {
        return [self::JUNIOR, self::NORMAL];
    }

    public static function getAllReal()
    {
        return [self::NORMAL, self::FREEZE, self::BANNED];
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
                return '会员';
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
        if (count($unique) == 0) {
            return ['g' => [], 'r' => 0, 'l' => 0];
        }
        $ret = self::get_tieba($unique);
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
                'code' => self::UNUSED,
            ];
        }
        $ret = Db::table('member')
            ->insertAll($data);
        return ['g' => $unique, 'r' => $ret, 'l' => count($unique)];
    }

    public static function get_open()
    {
        $map['code'] = self::UNUSED;
        $map['id'] = ['>', 863];
        $ret = Db::table('member')
            ->where($map)
            ->field('unique_name  as u')
            ->select();
        $already = [];
        foreach ($ret as $i) {
            $already[] = $i['u'];
        }
        trace("可选编号 " . count($already));
        sort($already);
        return $already;
    }

    public static function list_code($c, $debug = true)
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
        if ($debug) {
            trace("list_code $c " . count($already));
        }
        return $already;
    }

    public static function get_tieba($list)
    {
        if (count($list) == 0) {
            return [];
        }
        $res = Db::table('member')
            ->where(['unique_name' => ['in', $list]])
            ->field([
                'tieba_id as t',
                'unique_name as u'
            ])
            ->cache(600)
            ->select();
        return $res;
    }

    public static function daily()
    {
        $ret = self::list_code(self::TEMPUSE, false);
        foreach ($ret as $i) {
            self::Temp2Junior($i);
        }
        $limit = WxHanbj::addUnionID(WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS'));
        if ($limit > 0) {
            trace("未关注者：$limit");
        }

        $name = "MemberOper::daily()";
        if (cache("?$name")) {
            return;
        }
        cache($name, $name, 86300);

        $name = "MemberOper::daily()renew";
        $renew = !cache("?$name");
        if ($renew) {
            cache($name, $name, 86400 * 30);
        }

        $ret = self::list_code(self::TEMPUSE);
        foreach ($ret as $i) {
            self::Temp2Unused($i);
        }

        $ret = self::list_code(self::JUNIOR);
        foreach ($ret as $i) {
            self::Junior2Temp($i);
            if ($renew) {
                BonusOper::renew($i);
            }
        }

        $ret = self::list_code(self::BANNED);
        foreach ($ret as $i) {
            self::Banned2Normal($i);
        }

        $ret = self::list_code(self::NORMAL);
        foreach ($ret as $i) {
            self::Normal2Banned($i);
            if ($renew) {
                BonusOper::renew($i);
            }
        }
        self::fixBanned();
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
                cache($ca, $ca, 2 * 86400);
            }
            trace("$unique_name UNUSED TEMPUSE $ret");
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Unused2Temp $unique_name $e");
            if (false !== strpos($e, 'Duplicate')) {
                $e = "昵称 $tieba_id 已被使用";
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
        $data['year_time'] = -1;
        $data['bonus'] = 0;
        Db::startTrans();
        try {
            $ret = Db::table('member')
                ->where($map)
                ->update($data);
            if ($ret != 1) {
                throw new \Exception('1 fail');
            }
            CardOper::renew($unique_name);
            $map['code'] = self::UNUSED;
            $ret = Db::table('member')
                ->where($map)
                ->update(['openid' => null, 'unionid' => null]);
            if ($ret != 1) {
                throw new \Exception('2 fail');
            }
            trace("$unique_name TEMPUSE UNUSED 1");
            FeeOper::clear($unique_name);
            ActivityOper::clear($unique_name);
            FameOper::clear($unique_name);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
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
            CardOper::renew($unique_name);
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
            CardOper::renew($unique_name);
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
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Junior2Normal $unique_name $e");
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
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Normal2Banned $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }

    private static function fixBanned()
    {
        $map['code'] = self::BANNED;
        $map['year_time'] = ['>', intval(date('Y')) - 2];
        $ret = Db::table('member')
            ->where($map)
            ->field(['unique_name as u'])
            ->select();
        foreach ($ret as $i) {
            FeeOper::uncache($i['u']);
            self::Banned2Normal($i['u'], 0);
        }
        return $ret;
    }

    private static function Banned2Normal($unique_name, $panelty = 2)
    {
        if (FeeOper::owe($unique_name, $panelty)) {
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
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Banned2Normal $unique_name $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }
}
