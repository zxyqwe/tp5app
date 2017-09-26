<?php

namespace app\index\controller;

use app\index\GeoHelper;

class Zhongl
{
    public function _empty()
    {
        return '';
    }

    public function index()
    {
        return '';
    }

    public function geocode()
    {
        $pos = input('post.pos');
        if (empty($pos)) {
            return json(['msg' => 'pos']);
        }
        $geo = new GeoHelper();
        return $geo->getPos($pos);
    }
}