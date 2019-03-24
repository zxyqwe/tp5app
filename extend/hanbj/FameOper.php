<?php

namespace hanbj;

use think\Db;
use util\MysqlLog;

class FameOper
{
    const chairman = 0;//会长
    const vice_chairman = 1;//副会长
    const manager = 2;//部长
    const vice_manager = 3;//副部长
    const member = 4;//干事
    const assistant = 5;//助理
    const commissioner = 6;//专员
    const secretary = 7;//秘书长
    const vice_secretary = 8;//副秘书长
    const fame_chair = 9;//名誉会长
    const like_manager = 10;//代理部长
    const leave = 11;//离职
    const max_pos = 11;
    const order = [
        self::chairman,
        self::fame_chair,
        self::vice_chairman,
        self::secretary,
        self::manager,
        self::like_manager,
        self::vice_secretary,
        self::vice_manager,
        self::commissioner,
        self::assistant,
        self::member,
        self::leave
    ];

    public static function getUp()//会长层、部长
    {
        return self::get([
            self::chairman,
            self::vice_chairman,
            self::secretary,
            self::manager
        ]);
    }

    public static function getTop()//会长层
    {
        return self::get([
            self::chairman,
            self::vice_chairman
        ]);
    }

    public static function get($group)
    {
        $map['year'] = HBConfig::YEAR;
        $map['grade'] = ['in', $group];
        $ret = Db::table('fame')
            ->where($map)
            ->field('unique_name as u')
            ->cache(600)
            ->select();
        $data = [];
        foreach ($ret as $i) {
            $data[] = $i['u'];
        }
        return $data;
    }

    public static function cmp($a, $b)
    {
        $order = array_flip(self::order);
        //year desc,grade asc,label asc
        if ($a['y'] !== $b['y']) {
            return $a['y'] < $b['y'] ? 1 : -1;
        }
        if ($a['label'] !== $b['label']) {
            return $a['label'] < $b['label'] ? -1 : 1;
        }
        if ($a['grade'] !== $b['grade']) {
            return $order[$a['grade']] < $order[$b['grade']] ? -1 : 1;
        }
        return 0;
    }

    public static function sort($ret)
    {
        usort($ret, [self::class, 'cmp']);
        return $ret;
    }

    public static function clear($uname)
    {
        $map['unique_name'] = $uname;
        $data['unique_name'] = $uname . date("Y-m-d H:i:s");
        $ret = Db::table('fame')
            ->where($map)
            ->update($data);
        if (intval($ret) !== 0) {
            trace("Fame Clear $uname $ret", MysqlLog::INFO);
        }
    }

    public static function getOrder()
    {
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $res = Db::table('fame')
            ->alias('f')
            ->join($join)
            ->field([
                'f.unique_name',
                'tieba_id',
                'year as y',
                'grade',
                'label'
            ])
            ->select();
        $res = self::sort($res);
        $data = [];
        foreach ($res as $item) {
            $year = $item['y'];
            if (!isset($data[$year])) {
                $data[$year] = ['name' => $year];
                $data[$year]['teams'] = [];
            }
            $team = $item['label'];
            if (!isset($data[$year]['teams'][$team])) {
                $data[$year]['teams'][$team] = ['name' => $team];
                $data[$year]['teams'][$team]['ms'] = [];
            }
            $data[$year]['teams'][$team]['ms'][] = [
                'u' => $item['unique_name'],
                't' => $item['tieba_id'],
                'id' => $item['grade']
            ];
        }
        $data = array_values($data);
        foreach ($data as &$item) {
            $item['teams'] = array_values($item['teams']);
        }
        return $data;
    }
}