<?php

namespace hanbj;

use think\Db;
use think\exception\HttpResponseException;
use util\MysqlLog;

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
    const GROUP = [
        "乾", "坤", "震", "巽", "坎", "离", "艮", "兑",
        "夏", "商", "周", "秦", "汉", "晋", "隋", "唐", "宋", "明",
        "仁", //"义", "礼", "智", "信",
        "温", //"良", "恭", "俭", "让",
        "梅", //"兰", "竹", "菊", "柳",
        "钟", //"磬", "琴", "瑟", "笙",
    ];

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
        trace("可选编号 " . count($already), MysqlLog::LOG);
        sort($already);
        return $already;
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
        //        trace("list_code $c " . count($already), MysqlLog::LOG);
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

    public static function pretty_tieba($res)
    {
        $data = [];
        foreach ($res as $item) {
            $data[] = $item['u'] . '~' . $item['t'];
        }
        return $data;
    }

    public static function try_junior($openid)
    {
        $ret = Db::table('member')
            ->where([
                'openid' => $openid,
                'code' => self::TEMPUSE
            ])
            ->field([
                'unique_name as u',
            ])
            ->find();
        if (null === $ret) {
            return;
        }
        self::Temp2Junior($ret['u']);
    }

    public static function search_unionid($unionid)
    {
        $cache_key = "search_unionid$unionid";
        if (cache("?$cache_key")) {
            return json_decode(cache($cache_key), true);
        }
        $ret = Db::table('idmap')
            ->alias('f')
            ->join([
                ['member m', 'm.openid=f.openid', 'left']
            ])
            ->where([
                'f.unionid' => $unionid,
                'f.status' => ['neq', SubscibeOper::Unsubscribe]
            ])
            ->field([
                'm.unique_name',
                'f.openid',
                'm.code',
                'f.status'
            ])
            ->find();
        cache($cache_key, json_encode($ret), 600);
        return $ret;
    }

    public static function daily()
    {
        $ret = self::list_code(self::TEMPUSE);
        foreach ($ret as $i) {
            self::Temp2Junior($i);
        }
        BonusOper::up('nfee', '会费积分更新');
        BonusOper::up('activity', '活动积分更新');

        $name = "MemberOper::daily()";
        if (cache("?$name")) {
            return;
        }
        cache($name, $name, 86400 / 6 - 100);

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
            trace("$unique_name UNUSED TEMPUSE $ret", MysqlLog::INFO);
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Unused2Temp $unique_name $e", MysqlLog::ERROR);
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
                ->update(['openid' => null]);
            if ($ret != 1) {
                throw new \Exception('2 fail');
            }
            trace("$unique_name TEMPUSE UNUSED 1", MysqlLog::INFO);
            FeeOper::clear($unique_name);
            ActivityOper::clear($unique_name);
            FameOper::clear($unique_name);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            $e = $e->getMessage();
            trace("Temp2Unused $unique_name $e", MysqlLog::ERROR);
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
            trace("$unique_name TEMPUSE JUNIOR $ret", MysqlLog::INFO);
            CardOper::renew($unique_name);
            $union_id = Db::table('member')
                ->alias('m')
                ->join([
                    ['idmap f', 'm.openid=f.openid', 'left']
                ])
                ->where(['unique_name' => $unique_name])
                ->field([
                    'f.unionid'
                ])
                ->find();
            cache("search_unionid{$union_id['unionid']}", null);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Temp2Junior $unique_name $e", MysqlLog::ERROR);
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
            trace("$unique_name JUNIOR TEMPUSE $ret", MysqlLog::INFO);
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Junior2Temp $unique_name $e", MysqlLog::ERROR);
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
            trace("$unique_name JUNIOR NORMAL $ret", MysqlLog::INFO);
            trace(json_encode($data), MysqlLog::INFO);
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Junior2Normal $unique_name $e", MysqlLog::ERROR);
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
            trace("$unique_name NORMAL BANNED $ret", MysqlLog::INFO);
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Normal2Banned $unique_name $e", MysqlLog::ERROR);
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
            trace("$unique_name BANNED NORMAL $ret", MysqlLog::INFO);
            CardOper::renew($unique_name);
            return $ret == 1;
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Banned2Normal $unique_name $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
    }
}

/*
 CREATE TABLE `member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tieba_id` varchar(45) NOT NULL,
  `gender` varchar(4) NOT NULL,
  `phone` varchar(45) NOT NULL,
  `QQ` varchar(45) NOT NULL,
  `unique_name` varchar(8) NOT NULL,
  `master` varchar(45) NOT NULL,
  `eid` varchar(45) NOT NULL DEFAULT '?',
  `rn` varchar(45) NOT NULL DEFAULT '?',
  `mail` varchar(45) NOT NULL,
  `pref` varchar(45) NOT NULL,
  `web_name` varchar(45) NOT NULL,
  `code` tinyint(4) NOT NULL DEFAULT '0',
  `year_time` int(11) NOT NULL DEFAULT '2013',
  `openid` varchar(255) DEFAULT NULL,
  `bonus` int(11) NOT NULL
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name_UNIQUE` (`unique_name`),
  UNIQUE KEY `t_uniq` (`tieba_id`),
  UNIQUE KEY `openid_name` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 CREATE TABLE `idmap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(255) NOT NULL,
  `unionid` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name_UNIQUE` (`unionid`),
  UNIQUE KEY `openid_name` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */
