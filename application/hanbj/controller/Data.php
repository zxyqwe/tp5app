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
        $map['m.code'] = 0;
        $map['f.unoper'] = ['EXP', 'IS NULL'];
        $join = [
            ['fee f', 'm.unique_name=f.unique_name', 'left']
        ];
        $tmp = Db::table('member')
            ->alias('m')
            ->join($join)
            ->where($map)
            ->limit($offset, $size)
            ->group('f.unique_name')
            ->field([
                'sum(1) as s',
                'm.unique_name as u',
                'm.year_time as t'
            ])
            ->cache(600)
            ->select();
        $data['rows'] = $tmp;
        unset($map['f.unoper']);
        $total = Db::table('member')
            ->alias('m')
            ->where($map)
            ->cache(600)
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
        $map['f.code'] = 0;
        $tmp = Db::table('member')
            ->alias('f')
            ->where($map)
            ->limit($offset, $size)
            ->field([
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
            ])
            ->cache(600)
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('member')
            ->alias('f')
            ->where($map)
            ->cache(600)
            ->count();
        $data['total'] = $total;
        return json($data);
    }
}
