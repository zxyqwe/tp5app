<?php

namespace app\hanbj\controller;

class Error
{
    public function _empty()
    {
        abort(404, '页面不存在', []);
    }
}