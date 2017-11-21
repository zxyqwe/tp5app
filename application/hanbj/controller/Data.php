<?php

namespace app\hanbj\controller;

use app\hanbj\BonusOper;
use app\hanbj\FeeOper;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;


class Data extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        if ('succ' !== session('login')) {
            $res = json(['msg' => '未登录'], 400);
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        return '';
    }

    public function list_act()
    {
        $ret = Db::table('activity')
            ->field('distinct name')
            ->select();
        $data = [];
        foreach ($ret as $item) {
            $data[] = $item['name'];
        }
        return json($data);
    }

    public function json_act()
    {
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $up = input('get.up/b', false, FILTER_VALIDATE_BOOLEAN);
        $uname = input('get.uname', '');
        $act = input('get.act', '');
        if ($up) {
            $map['f.up'] = 0;
        } else {
            $map = array();
        }
        if (!empty($uname)) {
            $map['m.tieba_id|m.unique_name'] = ['like', '%' . $uname . '%'];
        }
        if (!empty($act) && '全部活动' !== $act) {
            $map['name'] = $act;
        }
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $tmp = Db::table('activity')
            ->alias('f')
            ->limit($offset, $size)
            ->where($map)
            ->join($join)
            ->order('act_time', 'desc')
            ->field([
                'f.id',
                'name as n',
                'f.unique_name as u',
                'oper as m',
                'act_time as y',
                'm.tieba_id as t',
                'f.up',
                'f.bonus'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('activity')
            ->alias('f')
            ->where($map)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_fee()
    {
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $up = input('get.up/b', false, FILTER_VALIDATE_BOOLEAN);
        $uname = input('get.uname', '');
        if ($up) {
            $map['f.up'] = 0;
        } else {
            $map = array();
        }
        if (!empty($uname)) {
            $map['m.tieba_id|m.unique_name'] = ['like', '%' . $uname . '%'];
        }
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $tmp = Db::table('nfee')
            ->alias('f')
            ->limit($offset, $size)
            ->where($map)
            ->join($join)
            ->order('fee_time', 'desc')
            ->field([
                'f.id',
                'f.unique_name as u',
                'oper as m',
                'fee_time as y',
                'f.code as c',
                'm.tieba_id as t',
                'f.up',
                'f.bonus'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_tree()
    {
        $map['f.master'] = ['neq', ''];
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

    public function json_all()
    {
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $search = input('get.search');
        if (!empty($search)) {
            $map['tieba_id|unique_name'] = ['like', '%' . $search . '%'];
        } else {
            $map = array();
        }
        $tmp = Db::table('member')
            ->alias('f')
            ->where($map)
            ->order('f.id')
            ->limit($offset, $size)
            ->cache(600)
            ->field([
                'f.id',
                'f.tieba_id as t',
                'f.gender as g',
                'f.phone as p',
                'f.QQ as q',
                'f.unique_name as u',
                'f.master as m',
                'f.rn as r',
                'f.mail as a',
                'f.pref as e',
                'f.web_name as w',
                'f.year_time as y',
                'f.bonus as b',
                'f.code',
                'f.openid'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('member')
            ->alias('f')
            ->where($map)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_detail()
    {
        $id = input('post.id', 1, FILTER_VALIDATE_INT);
        $map['m.id'] = $id;
        $fee = Db::table('member')
            ->alias('m')
            ->join('nfee f', 'm.unique_name=f.unique_name')
            ->where($map)
            ->order('f.fee_time', 'desc')
            ->field([
                'f.oper',
                'f.fee_time',
                'f.code'
            ])
            ->select();
        $act = Db::table('member')
            ->alias('m')
            ->join('activity f', 'm.unique_name=f.unique_name')
            ->where($map)
            ->order('act_time', 'desc')
            ->field([
                'oper',
                'name',
                'act_time'
            ])
            ->select();
        $data['fee'] = $fee;
        $data['act'] = $act;
        return json($data);
    }

    public function fee_search()
    {
        $name = input('get.name');
        if (empty($name)) {
            return json();
        }
        $map['tieba_id|unique_name'] = ['like', '%' . $name . '%'];
        $tmp = Db::table('member')
            ->alias('f')
            ->where($map)
            ->order('f.id')
            ->limit(10)
            ->field([
                'f.tieba_id as t',
                'f.unique_name as u',
            ])
            ->cache(600)
            ->select();
        return json($tmp);
    }

    public function fee_add()
    {
        $name = input('post.name/a', []);
        if (empty($name)) {
            return json(['msg' => 'empty name'], 400);
        }
        $type = input('post.type', FeeOper::ADD, FILTER_VALIDATE_INT);
        $type = $type == FeeOper::ADD ? 1 : -1;
        $data = [];
        $oper = session('name');
        $d = date("Y-m-d H:i:s");
        foreach ($name as $tmp) {
            $data[] = [
                'unique_name' => $tmp['u'],
                'oper' => $oper,
                'code' => $type,
                'fee_time' => $d,
                'bonus' => $type * BonusOper::FEE
            ];
            FeeOper::uncache($tmp['u']);
        }
        Db::startTrans();
        try {
            $res = Db::table('nfee')
                ->insertAll($data);
            if ($res === count($data)) {
                Db::commit();
            } else {
                Db::rollback();
                return json(['msg' => $res], 400);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['msg' => '' . $e], 400);
        }
        return json(['msg' => 'ok']);
    }

    public function vol_add()
    {
        $name = input('post.name/a', []);
        if (empty($name)) {
            return json(['msg' => 'empty name'], 400);
        }
        $data = [];
        $oper = session('name');
        $d = date("Y-m-d H:i:s");
        foreach ($name as $tmp) {
            $data[] = [
                'unique_name' => $tmp['u'],
                'oper' => $oper,
                'act_time' => $d,
                'bonus' => BonusOper::VOLUNTEER,
                'name' => BonusOper::ACT_NAME . '志愿者'
            ];
        }
        Db::startTrans();
        try {
            $res = Db::table('activity')
                ->insertAll($data);
            if ($res === count($data)) {
                Db::commit();
            } else {
                Db::rollback();
                return json(['msg' => $res], 400);
            }
        } catch (\Exception $e) {
            Db::rollback();
            $e = $e->__toString();
            preg_match('/Duplicate entry \'(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                $e = "错误！【 {$token[2]} 】已经被登记在【 {$token[1]} 】活动中了。请删除此项，重试。";
            }
            return json(['msg' => $e], 400);
        }
        return json(['msg' => 'ok']);
    }

    public function bonus_add()
    {
        $type = input('post.type');
        if ($type === '0') {
            return BonusOper::upFee();
        } else {
            return BonusOper::upAct();
        }
    }

    public function json_fameinit()
    {
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $res = Db::table('fame')
            ->alias('f')
            ->join($join)
            ->order('year desc,grade')
            ->field([
                'f.id',
                'f.unique_name as u',
                'tieba_id as t',
                'year as y',
                'grade as g',
                'label as l'
            ])
            ->select();
        return json($res);
    }

    public function fame_add()
    {
        $name = input('post.name/a', []);
        if (empty($name)) {
            return json(['msg' => 'empty name'], 400);
        }
        $year = input('post.year', 1, FILTER_VALIDATE_INT);
        $grade = input('post.grade', 0, FILTER_VALIDATE_INT);
        $label = input('post.label', '');
        if (empty($label) || $year < 1 || $year > 12 || $grade < 0 || $grade > 4) {
            return json(['msg' => '参数错误'], 400);
        }
        $data = [];
        foreach ($name as $tmp) {
            $data[] = [
                'unique_name' => $tmp['u'],
                'year' => $year,
                'grade' => $grade,
                'label' => $label
            ];
        }
        Db::startTrans();
        try {
            $res = Db::table('fame')
                ->insertAll($data);
            if ($res === count($data)) {
                Db::commit();
            } else {
                Db::rollback();
                return json(['msg' => $res], 400);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['msg' => '' . $e], 400);
        }
        return json(['msg' => 'ok']);
    }

    public function json_card()
    {
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $search = input('get.search');
        $map['f.code'] = ['EXP', 'IS NOT NULL'];
        if (!empty($search)) {
            $map['m.tieba_id|m.unique_name'] = ['like', '%' . $search . '%'];
        }
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $tmp = Db::table('card')
            ->alias('f')
            ->join($join)
            ->where($map)
            ->order('f.id', 'desc')
            ->limit($offset, $size)
            ->cache(600)
            ->group('m.unique_name')
            ->field([
                'f.id',
                'm.unique_name as u',
                'm.tieba_id as t',
                'm.code as c',
                'f.code as o',
                'f.status as s'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('card')
            ->alias('f')
            ->join($join)
            ->where($map)
            ->cache(600)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_order()
    {
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $res = Db::table('order')
            ->alias('f')
            ->join($join)
            ->order('f.id', 'desc')
            ->limit($offset, $size)
            ->field([
                'f.id',
                'm.unique_name as u',
                'm.tieba_id as e',
                'f.outid as o',
                'f.trans as t',
                'f.fee as f',
                'f.type as y',
                'f.value as v',
                'f.label as l',
                'f.time as i'
            ])
            ->select();
        $data['rows'] = $res;
        $total = Db::table('order')
            ->alias('f')
            ->count();
        $data['total'] = $total;
        return json($data);
    }
}
