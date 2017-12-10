<?php

namespace app\index\controller;

class Error
{
    public function _empty()
    {
        return json([], 404);
    }
}