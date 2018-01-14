<?php

namespace app\hanbj\controller;

use app\hanbj\MemberOper;
use app\hanbj\UserOper;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;

class Analysis extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if (UserOper::VERSION !== session('login')) {
            $res = redirect('https://app.zxyqwe.com/hanbj/pub/bulletin');
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/analysis_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, '页面不存在', [$action]);
    }

    public function json_group()
    {
        $ret = Db::table('member')
            ->field([
                'gender',
                'year_time',
                'SUBSTRING(unique_name,1,1) as u',
                'code'
            ])
            ->cache(600)
            ->select();
        return json($ret);
    }

    public function json_brief()
    {
        $ret = Db::table('member')
            ->field([
                'gender',
                'year_time',
                'SUBSTRING(unique_name,1,1) as u',
                'code'
            ])
            ->cache(600)
            ->select();
        foreach ($ret as &$item) {
            $item['gender'] = $item['gender'] === '男' ? 0 : 1;
        }
        return json($ret);
    }

    public function json_birth()
    {
        $map['code'] = MemberOper::NORMAL;
        $ret = Db::table('member')
            ->where($map)
            ->field([
                'SUBSTRING(eid,7,8) as eid',
                'gender',
                'year_time',
                'SUBSTRING(unique_name,1,1) as u'
            ])
            ->cache(600)
            ->select();
        $today = date('Ymd');
        foreach ($ret as &$item) {
            $tmp_eid = $item['eid'];
            if ($tmp_eid > 19491001 && $tmp_eid < $today) {
                $item['eid'] = $tmp_eid;
            } else {
                $item['eid'] = false;
            }
            $item['gender'] = $item['gender'] === '男' ? 0 : 1;
        }
        return json($ret);
    }
}
