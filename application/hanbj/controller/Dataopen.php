<?php

namespace app\hanbj\controller;

use think\Controller;
use think\Db;


class Dataopen extends Controller
{
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

    public function json_fame()
    {
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $res = Db::table('fame')
            ->alias('f')
            ->join($join)
            ->order('year desc,grade')
            ->field([
                'f.unique_name',
                'tieba_id',
                'year',
                'grade',
                'label'
            ])
            ->select();
        $data = [];
        foreach ($res as $item) {
            $year = $item['year'];
            if (!isset($data[$year])) {
                $data[$year] = ['name' => $year];
                $data[$year]['teams'] = [];
            }
            $team = $item['label'];
            if (!isset($data[$year]['teams'][$team])) {
                $data[$year]['teams'][$team] = ['name' => $team];
                $data[$year]['teams'][$team]['ms'] = [];
            }
            $data[$year]['teams'][$team]['ms'][] = [
                'u' => $item['unique_name'],
                't' => $item['tieba_id'],
                'id' => $item['grade']
            ];
        }
        $data = array_values($data);
        foreach ($data as &$item) {
            $item['teams'] = array_values($item['teams']);
        }
        return json($data);
    }

    public function json_bonus()
    {
        $tmp = Db::table('member')
            ->alias('m')
            ->cache(600)
            ->order('m.bonus', 'desc')
            ->limit(0, 50)
            ->field([
                'm.unique_name as u',
                'm.tieba_id as t',
                'm.bonus as o',
                'm.year_time as y'
            ])
            ->select();
        return json($tmp);
    }
}