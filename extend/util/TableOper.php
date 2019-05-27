<?php

namespace util;


use think\Db;
use think\exception\HttpResponseException;

class TableOper
{
    public static function generateOneTable($tmp, &$Tables_in_hanbj)
    {
        if (self::hasGenerated($tmp)) {
            return;
        }
        $tabledesc = Db::query("DESC `hanbj`.`$tmp`");
        $tablename = [];
        foreach ($tabledesc as $item2) {
            $tablename[] = $item2['Field'];
        }
        $Tables_in_hanbj[] = [
            'name' => $tmp,
            'desc' => implode(', ', $tablename),
            'cli' => "tableone/obj/$tmp"
        ];
        cache("tableone_$tmp", json_encode($tablename), 86400);
    }

    public static function hasGenerated($tmp)
    {
        return cache("?tableone_$tmp");
    }

    public static function getFieldsStr($tmp)
    {
        return '' . cache("tableone_$tmp");
    }

    public static function getFieldsArray($tmp)
    {
        return json_decode(self::getFieldsStr($tmp), true);
    }

    public static function assertInField($tmp, $name)
    {
        if (in_array($name, self::getFieldsArray($tmp))) {
            return;
        }
        $err = "field error $tmp $name";
        trace($err, MysqlLog::ERROR);
        if (request()->isAjax()) {
            $res = json(['msg' => $err], 400);
        } else {
            $res = redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        throw new HttpResponseException($res);
    }
}