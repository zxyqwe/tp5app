<?php

namespace app\morereading\controller;

class Index
{
    public function _empty()
    {
        return json([], 404);
    }
}
