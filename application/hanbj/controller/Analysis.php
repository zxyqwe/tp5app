<?php

namespace app\hanbj\controller;

use DateTimeImmutable;
use hanbj\MemberOper;
use hanbj\UserOper;
use think\Controller;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\response\Json;

class Analysis extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function json_birth()
    {
        $map['code'] = ['in', [MemberOper::NORMAL, MemberOper::JUNIOR]];
        $ret = Db::table('member')
            ->where($map)
            ->field([
                'birth as eid',
                'gender',
                'year_time',
                'SUBSTRING(unique_name,1,1) as u'
            ])
            ->cache(600)
            ->select();
        foreach ($ret as &$item) {
            if ($item['eid'] === '') {
                $item['eid'] = false;
                continue;
            }
            $tmp_eid = DateTimeImmutable::createFromFormat('Y-m-d', $item['eid']);
            $item['eid'] = intval($tmp_eid->format('Ymd'));
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

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function json_tree()
    {
        $map['f.master'] = ['neq', ''];
        $map['f.code'] = ['in', [MemberOper::NORMAL, MemberOper::BANNED, MemberOper::FREEZE]];
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
