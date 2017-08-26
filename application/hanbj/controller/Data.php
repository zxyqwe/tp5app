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
        'valid_id' => ['except' => 'json_login,json_bulletin,json_fame']
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

    public function json_login()
    {
        $capt = input('post.capt');
        if (!captcha_check($capt)) {
            return json(['msg' => '验证码错误'], 400);
        }
        $mm = input('post.mm');
        $nonstr = session('nonstr');
        $user = input('post.user');
        $tmp = Db::table('user')->where(['name' => $user])->value('mm');
        $tmp = strtolower($tmp) . $nonstr;
        $tmp = sha1($tmp);
        if ($mm !== $tmp) {
            return json(['msg' => '密码错误'], 400);
        }
        session('login', 'succ');
        session('name', $user);
        return json(['msg' => ' 登录成功'], 200);
    }

    public function json_bulletin()
    {
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $search = input('get.search');
        if (!empty($search)) {
            $map['m.unique_name'] = ['like', '%' . $search . '%'];
        }
        $map['m.code'] = 0;
        $join = [
            ['nfee f', 'm.unique_name=f.unique_name', 'left']
        ];
        $tmp = Db::table('member')
            ->alias('m')
            ->join($join)
            ->where($map)
            ->limit($offset, $size)
            ->cache(600)
            ->group('m.unique_name')
            ->field([
                'm.unique_name as u',
                'm.year_time as t',
                'sum(f.code) as b'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('member')
            ->alias('m')
            ->where($map)
            ->cache(600)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_act()
    {
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $up = input('get.up/b', false, FILTER_VALIDATE_BOOLEAN);
        if ($up) {
            $map['f.up'] = 0;
        } else {
            $map = array();
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
        if ($up) {
            $map['f.up'] = 0;
        } else {
            $map = array();
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
            $map['tieba_id'] = ['like', '%' . $search . '%'];
        } else {
            $map = array();
        }
        $tmp = Db::table('member')
            ->alias('f')
            ->where($map)
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
                'f.code'
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
        $map['m.code'] = 0;
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

    public function bonus_add()
    {
        $type = input('post.type');
        if ($type === '0') {
            return BonusOper::upFee();
        } else {
            return BonusOper::upAct();
        }
    }

    public function json_fame()
    {
        return json(['msg' => []]);
    public function fame_add()
    {
        $name = input('post.name/a', []);
        if (empty($name)) {
            return json(['msg' => 'empty name'], 400);
        }
        $year = input('post.year', 1, FILTER_VALIDATE_INT);
        $grade = input('post.grade', 0, FILTER_VALIDATE_INT);
        $label = input('post.label', '');
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
}
