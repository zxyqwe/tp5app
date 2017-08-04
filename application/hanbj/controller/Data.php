<?php

namespace app\hanbj\controller;

use think\Db;


class Data
{
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
            ->group('m.unique_name')
            ->field([
                'count(oper) as s',
                'm.unique_name as u',
                'm.year_time as t',
                'sum(f.code) as n'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('member')
            ->alias('m')
            ->where($map)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_act()
    {
        if ('succ' !== session('login')) {
            return json(['msg' => '未登录'], 400);
        }
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $tmp = Db::table('activity')
            ->alias('f')
            ->limit($offset, $size)
            ->join($join)
            ->order('act_time', 'desc')
            ->field([
                'f.id',
                'name as n',
                'f.unique_name as u',
                'oper as m',
                'act_time as y',
                'm.tieba_id as t',
                'f.up'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('activity')
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_fee()
    {
        if ('succ' !== session('login')) {
            return json(['msg' => '未登录'], 400);
        }
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $tmp = Db::table('nfee')
            ->alias('f')
            ->limit($offset, $size)
            ->join($join)
            ->order('fee_time', 'desc')
            ->field([
                'f.id',
                'f.unique_name as u',
                'oper as m',
                'fee_time as y',
                'f.code as c',
                'm.tieba_id as t',
                'f.up'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('nfee')
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_all()
    {
        if ('succ' !== session('login')) {
            return json(['msg' => '未登录'], 400);
        }
        $size = input('get.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('get.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $search = input('get.search');
        if (!empty($search)) {
            $map['tieba_id'] = ['like', '%' . $search . '%'];
        }
        $map['f.code'] = 0;
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
                'f.bonus as b'
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
        if ('succ' !== session('login')) {
            return json(['msg' => '未登录'], 400);
        }
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
        if ('succ' !== session('login')) {
            return json(['msg' => '未登录'], 400);
        }
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
        $type = input('post.type', 0, FILTER_VALIDATE_INT);
        $type = $type == 0 ? 0 : 1;
        $data = [];
        $oper = session('name');
        $d = date("Y-m-d H:i:s");
        foreach ($name as $tmp) {
            $data[] = [
                'unique_name' => $tmp['u'],
                'oper' => $oper,
                'code' => $type,
                'fee_time' => $d
            ];
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
            return json(['msg' => json_encode($e)], 400);
        }
        return json(['msg' => 'ok']);
    }
}
