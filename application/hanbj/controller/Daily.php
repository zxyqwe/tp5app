<?php

namespace app\hanbj\controller;

use hanbj\BonusOper;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;


class Daily extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/daily_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function json_act()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $up = input('post.up/b', false, FILTER_VALIDATE_BOOLEAN);
        $uname = input('post.uname', '');
        $act = input('post.act', '');
        if ($up) {
            $map['f.up'] = 0;
        } else {
            $map = array();
        }
        if (!empty($uname)) {
            $map['m.tieba_id|f.unique_name'] = ['like', '%' . $uname . '%'];
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
                'f.bonus',
                'm.code'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('activity')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_all()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $search = input('post.search');
        $level = input('post.up/a', []);
        $level = array_intersect($level, range(0, 4));
        if (count($level) < 1) {
            return json(['rows' => [], 'total' => 0]);
        }
        $map['code'] = ['in', $level];
        if (!empty($search)) {
            $map['tieba_id|unique_name|rn'] = ['like', '%' . $search . '%'];
        }
        $tmp = Db::table('member')
            ->alias('f')
            ->where($map)
            ->order('f.id')
            ->limit($offset, $size)
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
                'f.openid',
                'f.eid'
            ])
            ->select();
        $today = date('Ymd');
        foreach ($tmp as &$item) {
            $tmp_eid = substr($item['eid'], 6, 8);
            if ($tmp_eid > 19491001 && $tmp_eid < $today) {
                $item['eid'] = $tmp_eid;
            }
        }
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
                'f.code',
                'f.bonus',
                'up'
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
                'act_time',
                'f.bonus',
                'up'
            ])
            ->select();
        $fame = Db::table('member')
            ->alias('m')
            ->join('fame f', 'm.unique_name=f.unique_name')
            ->where($map)
            ->order('year', 'desc')
            ->field([
                'year',
                'grade',
                'label'
            ])
            ->select();
        $data['fee'] = $fee;
        $data['act'] = $act;
        $data['fame'] = $fame;
        return json($data);
    }

    public function json_card()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $search = input('post.search');
        $map['f.code'] = ['exp', Db::raw('is not null')];
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

    public function json_fee()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $up = input('post.up/b', false, FILTER_VALIDATE_BOOLEAN);
        $uname = input('post.uname', '');
        if ($up) {
            $map['f.up'] = 0;
        } else {
            $map = array();
        }
        if (!empty($uname)) {
            $map['m.tieba_id|f.unique_name'] = ['like', '%' . $uname . '%'];
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
                'f.bonus',
                'm.code'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('nfee')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function bonus_add()
    {
        $type = input('post.type');
        if ($type === '0') {
            return BonusOper::up('nfee', '会费积分更新');
        } else {
            return BonusOper::up('activity', '活动积分更新');
        }
    }

    public function list_act()
    {
        $ret = Db::table('activity')
            ->order('id desc')
            ->field('distinct name')
            ->limit(10000)
            ->cache(600)
            ->select();
        $data = [];
        foreach ($ret as $item) {
            $data[] = $item['name'];
        }
        return json($data);
    }
}
