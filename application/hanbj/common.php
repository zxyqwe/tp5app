<?php

namespace app\hanbj;

use think\Db;

Db::listen(function ($sql, $time, $explain) {
    if ($time < 0.5) {
        return;
    }
    trace("[$time 秒]$sql", 'sql');
});