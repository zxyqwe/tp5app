<?php

namespace app\hanbj\controller;


class Index
{
    public function index()
    {
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        return substr($access, 0, 5);
    }
}
