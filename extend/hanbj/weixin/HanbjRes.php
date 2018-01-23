<?php

namespace hanbj\weixin;

use app\WxPayDataBase;


class HanbjRes extends WxPayDataBase
{
    public function setValues($value)
    {
        $this->values = $value;
        return $this->MakeSign();
    }
}

