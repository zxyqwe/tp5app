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
        if ($hour < 2 || $hour > 6) {
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
}