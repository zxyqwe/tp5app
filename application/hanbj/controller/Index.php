<?php

namespace app\hanbj\controller;


use app\hanbj\BonusOper;
use app\hanbj\WxHanbj;
use think\exception\HttpResponseException;

class Index
{
    protected function valid_id()
    {
        if ('succ' !== session('login')) {
            $res = redirect('/hanbj/index/bulletin');
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return '';
    }

    public function index()
    {
        if ('succ' === session('login')) {
            return redirect('/hanbj/index/home');
        }
        $nonstr = getNonceStr();
        session('nonstr', $nonstr);
        return view('login', ['nonstr' => $nonstr]);
    }

    public function old()
    {
        return redirect('/hanbj/index/bulletin');
    }

    public function bulletin()
    {
        return view('bulletin');
    }

    public function home()
    {
        $this->valid_id();
        return view('home', ['name' => session('name')]);
    }

    public function all()
    {
        $this->valid_id();
        return view('all');
    }

    public function feelog()
    {
        $this->valid_id();
        return view('feelog');
    }

    public function actlog()
    {
        $this->valid_id();
        return view('actlog');
    }

    public function fee()
    {
        $this->valid_id();
        return view('fee');
    }

    public function create()
    {
        $this->valid_id();
        return view('create');
    }

    public function tree()
    {
        $this->valid_id();
        return view('tree');
    }

    public function token()
    {
        $this->valid_id();
        $length = 10;
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $map['Access Key'] = substr($access, 0, $length);
        $map['Js Api'] = substr(WxHanbj::jsapi($access), 0, $length);
        $map['Ticket Api'] = substr(WxHanbj::ticketapi($access), 0, $length);
        $map['会费增加积分'] = BonusOper::FEE;
        $map['活动增加积分'] = BonusOper::ACT;
        $map['活动预置名称'] = BonusOper::ACT_NAME;
        $map['当前工作人员'] = implode('，', BonusOper::WORKER);
        return view('token', ['data' => $map]);
    }
}