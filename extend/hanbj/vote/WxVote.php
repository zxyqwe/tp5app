<?php

namespace hanbj\vote;

use hanbj\MemberOper;
use think\Db;
use think\exception\HttpResponseException;

class WxVote
{
    //乾壬申~夜娘_魁児，乾甲申~鸿胪寺少卿，坤丁酉~素?问，离庚寅~紫菀灯芯，艮甲辰~采娈
    const obj = ['乾壬申', '乾甲申', '坤丁酉', '离庚寅', '艮甲辰'];

    public static function initView()
    {
        $member_code = session('member_code');
        if ($member_code === null || intval($member_code) !== MemberOper::NORMAL) {
            return null;
        }

        $map = self::getMap();
        $ret = Db::table('vote')
            ->where([
                'unique_name' => session('unique_name'),
                'year' => WxOrg::year
            ])
            ->field([
                'ans'
            ])
            ->find();
        if (null === $ret) {
            $ret = [];
        } else {
            $ret = explode(',', $ret['ans']);
        }
        $front = [];
        foreach ($ret as $item) {
            if (!isset($map[$item])) {
                continue;
            }
            $map[$item]['sel'] = true;
            $front[] = $map[$item];
            unset($map[$item]);
        }
        foreach (array_values($map) as $item) {
            $front[] = $item;
        }
        return $front;
    }

    private static function getMap()
    {
        $res = MemberOper::get_tieba(self::obj);
        $map = [];
        foreach ($res as $item) {
            $map[$item['u']] = $item;
            $map[$item['u']]['s'] = $item['u'] . '~' . $item['t'];
            $map[$item['u']]['sel'] = false;
        }
        return $map;
    }

    public static function addAns($uniq, $ans)
    {
        $data = [
            'unique_name' => $uniq,
            'year' => WxOrg::year,//这一届投票下一届
            'ans' => implode(',', $ans)
        ];
        try {
            $ret = Db::table('vote')
                ->where([
                    'unique_name' => $uniq,
                    'year' => WxOrg::year,//这一届投票下一届
                ])
                ->data(['ans' => $data['ans']])
                ->update();
            if ($ret <= 0) {
                Db::table('vote')
                    ->insert($data);
                trace("选举add $uniq {$data['ans']}");
            } else {
                trace("投票update $uniq {$data['ans']}");
            }
        } catch (\Exception $e) {
            $e = $e->getMessage();
            preg_match('/Duplicate entry \'(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                return json(['msg' => 'OK']);
            }
            trace("Test Vote $e");
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
        return json(['msg' => 'OK']);
    }

    public static function getResult()
    {
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left'],
            ['fame f', 'f.unique_name=f.unique_name and f.year=' . WxOrg::year, 'left']
        ];
        $map = [
            'm.code' => MemberOper::NORMAL,
            'v.year' => WxOrg::year
        ];
        $ans = Db::table('vote')
            ->alias('v')
            ->join($join)
            ->where($map)
            ->cache(600)
            ->field([
                'v.ans as a',
                'f.grade as g'
            ])
            ->select();
        return $ans;
    }
}
