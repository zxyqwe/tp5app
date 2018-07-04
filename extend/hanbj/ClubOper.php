<?php

namespace hanbj;


use think\Db;
use think\exception\HttpResponseException;

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
            if ($ret === 1) {
                self::actClub($pk);
            }
        } catch (\Exception $e) {
            return json(['msg' => $e->getMessage()], 400);
        }
        return json('修改成功！');
    }

    private static function actClub($pk)
    {
        $ret = Db::table('club')
            ->where(['id' => $pk])
            ->field(['owner', 'worker'])
            ->find();

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
            return json(['msg' => $e->getMessage()], 400);
        }
    }
}