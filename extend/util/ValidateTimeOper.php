<?php

namespace util;


class ValidateTimeOper
{
    public static function IsDayUp()
    {
        $now = getdate();
        $hour = $now['hours']; // 0 ~ 23
        if ($hour < 9 || $hour > 19) {
            return false;
        }
        return true;
    }

    public static function GoodForBackup()
    {
        $now = getdate();
        $hour = $now['hours']; // 0 ~ 23
        if ($hour < 2 || $hour > 19) {
            return false;
        }
        return true;
    }

    public static function IsYearEnd()
    {
        $now = getdate();
        $month = $now['mon']; // 1 ~ 12
        if ($month > 11) {
            return true;
        }
        return false;
    }

    public static function NotGoodForAnything()
    {
        $now = getdate();
        $hour = $now['hours']; // 0 ~ 23
        $minutes = $now['minutes']; // 0 ~ 59
        if ($hour === 0 && $minutes < 3) {
            return true;
        }
        if ($hour === 23 && $minutes > 56) {
            return true;
        }
        return false;
    }
}