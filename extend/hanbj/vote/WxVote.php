<?php

namespace hanbj\vote;

use think\Db;

class WxVote
{
    private static function weight()
    {
        $org = new WxOrg(1);
        $ret = Db::table('vote')
            ->where([
                'year' => WxOrg::year
            ])->field([
                'unique_name as u',
                'ans'
            ])
            ->select();
        $data = [];
        foreach ($ret as $i) {
            $ans = json_decode($i['ans'], true);
            $w = 1.0;
            if (in_array($i['u'], $org->lower)) {
                $w = 1.5;
            } elseif (in_array($i['u'], $org->upper)) {
                $w = 2.0;
            }
            foreach ($ans as $j) {
                if (!isset($data[$j])) {
                    $data[$j] = 0;
                }
                $data[$j] += $w;
            }
        }
        return $data;
    }

    private static function watcher($unique)
    {
        return in_array($unique, ['坎丙午']);
    }

    private static function make_res($unique_name)
    {
        $data = [];
        $data['result'] = self::watcher($unique_name);
        if ($data['result']) {
            $data['res_ans'] = self::weight();
        }
        return $data;
    }

    public static function result($unique_name)
    {
        $data = self::make_res($unique_name);
        $data['ans'] = Db::table('vote')
            ->where([
                'year' => WxOrg::year,
                'unique_name' => $unique_name
            ])->value('ans');
        $data['unvote'] = false;
        if (is_null($data['ans'])) {
            $data['ans'] = [];
            $data['unvote'] = true;
        } else {
            $data['ans'] = json_decode($data['ans'], true);
        }
        return json_encode($data);
    }
}
