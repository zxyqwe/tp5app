<?php

namespace app\hanbj\controller;


class Index
{
    public function index()
    {
        if ('succ' === session('login')) {
            return redirect('/hanbj/index/home');
        }
        $nonstr = getNonceStr();
        session('nonstr', $nonstr);
        return view('login', ['nonstr' => $nonstr]);
    }

    public function home()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('home', ['name' => session('name')]);
    }

    public function old()
    {
        return redirect('/hanbj/index/bulletin');
    }

    public function bulletin()
    {
        return view('bulletin');
    }

    public function all()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('all');
    }

    public function feelog()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('feelog');
    }

    public function actlog()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('actlog');
    }

    public function fee()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('fee');
    }

    public function create()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('create');
    }

    public function tree()
    {
        if ('succ' !== session('login')) {
            return redirect('/hanbj/index/bulletin');
        }
        return view('tree');
    }
}
