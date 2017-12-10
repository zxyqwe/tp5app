<?php

namespace app\morereading\controller;

class Error
{
    public function _empty()
    {
        return json([], 404);
    }
}