<?php

namespace hanbj;

use think\Db;

class BonusOper
{
    const FEE = 30;
    const ACT = 20;
    const VOLUNTEER = 30;
    const ACT_NAME = '2018小年';
    const _WORKER = ['兑壬子', '兑癸卯', '兑癸巳', '兑甲辰'];

    //陌上歌未央, 狼破军魂, 何处画夕阳, 姑娘_请回眸

    public static function getWorkers()
    {
        $data = array_merge(FameOper::getUp(), self::_WORKER);
        //zxyqwe, 魁儿, 花西, 哈利, 紫菀
        $data = array_merge($data, ['坎丙午', '乾壬申', '离丙申', '巽丁巳', '离庚寅']);
        return array_unique($data);
    }

    public static function renew($uname)
    {
        $bonus = self::reCalc($uname);
        $map['unique_name'] = $uname;
        $map['code'] = ['in', MemberOper::getMember()];
        $ret = Db::table('member')
            ->where($map)
            ->setField('bonus', $bonus);
        if ($ret > 0) {
            CardOper::renew($uname);
        }
        return $bonus;
    }

    public static function reCalc($uname)
    {
        $key = "BonusOper::reCalc{$uname}";
        if (cache("?{$key}")) {
            return intval(cache($key));
        }
        $map['up'] = 1;
        $map['unique_name'] = $uname;
        $act = Db::table('activity')
            ->where($map)
            ->sum('bonus');
        $res = Db::table('nfee')
            ->where($map)
            ->sum('bonus');
        $all_b = intval($act) + intval($res);
        cache($key, $all_b, 600);
        return intval(cache($key));
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
                    $label,
                    $bonus,
                    intval($item['bonus']) + $bonus);
            } else {
                trace("{$item['unique_name']} 没有会员卡");
            }
        } catch (\Exception $e) {
            Db::rollback();
            $e = $e->getMessage();
            return json(['msg' => $e], 400);
        }
        return json(['msg' => 'ok', 'c' => 1]);
    }

    public static function getTop()
    {
        $map['code'] = ['in', MemberOper::getMember()];
        $tmp = Db::table('member')
            ->alias('m')
            ->cache(600)
            ->order('m.bonus', 'desc')
            ->where($map)
            ->limit(51, 1)
            ->field([
                'm.bonus as o'
            ])
            ->select();
        $map['bonus'] = ['>=', intval($tmp[0]['o'])];
        $map['unique_name'] = ['neq', '坎丙午'];
        $tmp = Db::table('member')
            ->alias('m')
            ->cache(600)
            ->order('m.bonus', 'desc')
            ->where($map)
            ->field([
                'm.unique_name as u',
                'm.tieba_id as t',
                'm.bonus as o',
                'm.year_time as y'
            ])
            ->select();
        $tmp_list = [];
        $tmp_bonus = 0;
        $base = 0;
        foreach ($tmp as $key => $item) {
            if ($item['o'] != $tmp_bonus) {
                $base = $key + 1;
                if ($base > 50) {
                    break;
                }
                $tmp_bonus = $item['o'];
            }
            $item['i'] = $base;
            $tmp_list[] = $item;
        }
        return $tmp_list;
    }

    public static function mod_ret($bonus)
    {
        $ret = '50名之后';
        $bonus_top = self::getTop();
        foreach ($bonus_top as $item) {
            if ($item['o'] <= $bonus) {
                $ret = "第{$item['i']}名";
                break;
            }
        }
        return $ret;
    }
}
