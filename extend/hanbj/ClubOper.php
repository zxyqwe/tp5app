<?php

namespace hanbj;


use think\Db;

class ClubOper
{
    public static function grantClub($pk, $value)
    {
        if ($value === 0) {
            return json(['msg' => '参数错误'], 400);
        }
        $unique = session('unique_name');
        try {
            $ret = Db::table('club')
                ->data(['code' => $value])
                ->where(['id' => $pk, 'code' => 0])
                ->update();
            trace("Club Edit $unique $pk $value");
            if ($ret === 1 && $value === 1) {
                self::actClub($pk);
            }
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("grantClub $e");
            return json(['msg' => $e], 400);
        }
        return json('修改成功！');
    }

    private static function actClub($pk)
    {
        $join = [
            ['member f', 'm.owner=f.unique_name', 'left'],
            ['member n', 'n.unique_name=m.worker', 'left']
        ];
        $ret = Db::table('club')
            ->alias('m')
            ->where(['m.id' => $pk])
            ->join($join)
            ->field([
                'name',
                'm.owner as o1',
                'm.worker as o2',
                'f.openid as n1',
                'n.openid as n2',])
            ->find();
        ActivityOper::signAct($ret['o1'], $ret['n1'], $ret['name'], 8);
        ActivityOper::signAct($ret['o2'], $ret['n2'], $ret['name'], 8);
    }

    public static function signClub($user, $openid, $pk)
    {
        $d = date("Y-m-d");
        $unique_name = session('unique_name');
        $map['owner|worker'] = $unique_name;
        $map['id'] = $pk;
        $map['start_time'] = ['LE', $d];
        $map['stop_time'] = ['GE', $d];
        $ret = Db::table('club')
            ->where($map)
            ->field('name')
            ->find();
        if ($ret === null) {
            return json(['msg' => '没有活动'], 400);
        }
        ActivityOper::signAct($user, $openid, $ret['name'], 5);
        return json(['msg' => 'ok']);
    }

    public static function applyClub($a_name, $w_name, $a_time, $e_time)
    {
        if (strlen($a_name) < 1 || $a_time > $e_time) {
            return json(['msg' => '参数错误'], 400);
        }
        $a_name = date('Y') . $a_name;
        $unique_name = session('unique_name');
        $data = [
            'name' => $a_name,
            'owner' => $unique_name,
            'worker' => $w_name,
            'start_time' => $a_time,
            'stop_time' => $e_time,
            'code' => 0
        ];
        try {
            Db::table('club')
                ->data($data)
                ->insert();
            trace("Club Apply " . json_encode($data));
            return json('提交成功！');
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("applyClub $e");
            return json(['msg' => $e], 400);
        }
    }
}
