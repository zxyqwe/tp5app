<?php
namespace app\index\controller;

class Index
{
    public function index($did=3)
    {
        cache('123','123');
        return [(int)input('get.id')+$did];
    }
}
