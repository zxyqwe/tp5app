<?php

namespace hanbj\vote;

use hanbj\FameOper;
use hanbj\HBConfig;
use hanbj\MemberOper;
use think\Db;
use think\exception\HttpResponseException;
use util\MysqlLog;

class WxVote
{
    const end_time = 1545458400; // mktime(14, 00, 00, 12, 22, 2018);


    public static function initView()
    {
        $member_code = session('member_code');
        if (!is_numeric($member_code) || intval($member_code) !== MemberOper::NORMAL) {
            return null;
        }

        $map = self::getMap();
        $ret = Db::table('vote')
            ->where([
                'unique_name' => session('unique_name'),
                'year' => HBConfig::YEAR
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
        $res = MemberOper::get_tieba(HBConfig::NEXT);
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
            'year' => HBConfig::YEAR,//这一届投票下一届
            'ans' => implode(',', $ans)
        ];
        try {
            $ret = Db::table('vote')
                ->where([
                    'unique_name' => $uniq,
                    'year' => HBConfig::YEAR,//这一届投票下一届
                ])
                ->data(['ans' => $data['ans']])
                ->update();
            if ($ret <= 0) {
                Db::table('vote')
                    ->insert($data);
                trace("选举add $uniq {$data['ans']}", MysqlLog::INFO);
            } else {
                trace("选举update $uniq {$data['ans']}", MysqlLog::INFO);
            }
        } catch (\Exception $e) {
            $e = $e->getMessage();
            preg_match('/Duplicate entry \'(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                return json(['msg' => 'OK']);
            }
            trace("Test Vote $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
        return json(['msg' => 'OK']);
    }

    public static function getResult()
    {
        $cache_name = 'WxVote::getResult';
        if (cache("?$cache_name")) {
            return json_decode(cache($cache_name), true);
        }

        $join = [
            ['member m', 'm.unique_name=v.unique_name', 'left'],
            ['fame f', 'f.unique_name=v.unique_name and f.year=' . HBConfig::YEAR, 'left']
        ];
        $map = [
            'm.code' => MemberOper::NORMAL,
            'v.year' => HBConfig::YEAR
        ];
        $ans = Db::table('vote')
            ->alias('v')
            ->join($join)
            ->where($map)
            ->field([
                'v.ans as a',
                'f.grade as g'
            ])
            ->select();
        $last = self::end_time - time();
        if ($last < 0) {
            $last = ' 00 秒';
        } elseif ($last < 86400) {
            $last = date("H 小时 i 分钟 s 秒", $last - 8 * 3600 - 86400);
        } else {
            $last = date("d 天 H 小时 i 分钟 s 秒", $last - 8 * 3600 - 86400);
        }
        $ans = [
            'zg' => self::test_ZG($ans),
            'pw' => self::test_PW($ans),
            'ref' => date("Y-m-d H:i:s"),
            'last' => $last
        ];
        cache($cache_name, json_encode($ans), 600);
        return $ans;
    }

    private static function test_ZG($ans)
    {
        $total = 0;
        $candidate = [];
        $map = self::getMap();
        foreach (HBConfig::NEXT as $item) {
            $candidate[$map[$item]['s']] = 0;
        }
        foreach ($ans as $item) {
            $tmp = explode(',', $item['a']);
            if ($item['g'] === null) {
                $weight = 1;
            } elseif (in_array(intval($item['g']), [
                FameOper::chairman,
                FameOper::vice_chairman,
                FameOper::manager,
                FameOper::vice_manager,
                FameOper::commissioner,
                FameOper::secretary,
                FameOper::vice_secretary,
                FameOper::fame_chair,
                FameOper::like_manager
            ])) {
                $weight = 3;
            } else {
                if ($item['g'] !== FameOper::leave) {
                    $weight = 2;
                } else {
                    $weight = 1;
                }
            }
            $total += $weight;
            foreach ($tmp as $idx) {
                $candidate[$map[$idx]['s']] += $weight;
            }
        }
        return ['tot' => $total, 'detail' => $candidate];
    }

    private static function test_PW($ans)
    {
        $total = 0;
        $candidate = [];
        $map = self::getMap();
        foreach (HBConfig::NEXT as $item) {
            $candidate[$map[$item]['s']] = 0;
        }
        foreach ($ans as $item) {
            if ($item['g'] === null) {
                continue;
            }
            $weight = count(HBConfig::NEXT);
            $total += $weight;
            $tmp = explode(',', $item['a']);
            foreach ($tmp as $idx) {
                $candidate[$map[$idx]['s']] += $weight;
                $weight--;
            }
        }
        return ['tot' => $total, 'detail' => $candidate];
    }
}
