<?php

namespace hanbj\vote;

use hanbj\MemberOper;
use think\Db;

class WxVote
{
    //乾壬申~夜娘_魁児，乾甲申~鸿胪寺少卿，坤丁酉~素?问，离庚寅~紫菀灯芯，艮甲辰~采娈
    const obj = ['乾壬申', '乾甲申', '坤丁酉', '离庚寅', '艮甲辰'];

    public static function initView()
    {
        $member_code = session('member_code');
        if ($member_code === null && !in_array(intval($member_code), MemberOper::getMember())) {
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
            $ret = self::obj;
        } else {
            $ret = explode(',', $ret);
        }
        $view = [];
        foreach ($ret as $item) {
            $view[] = $map[$item];
        }
        return $view;
    }

    private static function getMap()
    {
        $res = MemberOper::get_tieba(self::obj);
        $map = [];
        foreach ($res as $item) {
            $map[$item['u']] = $item;
            $map[$item['u']]['s'] = $item['u'] . '~' . $item['t'];
        }
        return $map;
    }
}
