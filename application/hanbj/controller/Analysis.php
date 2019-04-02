<?php

namespace app\hanbj\controller;

use hanbj\MemberOper;
use hanbj\UserOper;
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
        UserOper::valid_pc($this->request->isAjax());
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/analysis_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
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
            switch ($item['gender']) {
                case '男':
                    $item['gender'] = 0;
                    break;
                case '女':
                    $item['gender'] = 1;
                    break;
                default:
                    $item['gender'] = 2;
                    break;
            }
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
            switch ($item['gender']) {
                case '男':
                    $item['gender'] = 0;
                    break;
                case '女':
                    $item['gender'] = 1;
                    break;
                default:
                    $item['gender'] = 2;
                    break;
            }
        }
        return json($ret);
    }

    public function json_tree()
    {
        $map['f.master'] = ['neq', ''];
        $map['f.code'] = ['not in', [MemberOper::UNUSED, MemberOper::JUNIOR, MemberOper::TEMPUSE]];
        $tmp = Db::table('member')
            ->alias('f')
            ->where($map)
            ->cache(600)
            ->field([
                'f.tieba_id as t',
                'f.unique_name as u',
                'f.master as m',
                'f.code as c'
            ])
            ->select();
        return json($tmp);
    }
}
