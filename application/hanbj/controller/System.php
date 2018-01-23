<?php

namespace app\hanbj\controller;

use hanbj\UserOper;
use think\Controller;
use think\Db;
use hanbj\BonusOper;
use app\hanbj\LogUtil;
use hanbj\MemberOper;
use hanbj\weixin\WxHanbj;
use hanbj\vote\WxOrg;
use think\exception\HttpResponseException;

class System extends Controller
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
        if (is_file(__DIR__ . "/../tpl/system_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, '页面不存在', [$action]);
    }

    public function runlog($data = -1)
    {
        MemberOper::daily();
        if (session('name') !== '坎丙午') {
            return redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        if (-1 < $data) {
            $par = input('post.par');
            $chi = input('post.chi');
            $data = intval($data);
            if (!is_numeric($par) || !is_numeric($chi)) {
                return json(['msg' => $par . '-' . $chi], 400);
            }
            $dir = LOG_PATH . DIRECTORY_SEPARATOR . $par . DIRECTORY_SEPARATOR . $chi . '.log';
            if (!is_file($dir)) {
                return json(['msg' => $dir], 400);
            }
            $f = file_get_contents($dir);
            return json([
                'text' => substr($f, $data),
                'len' => strlen($f)
            ]);
        }
        $data = LogUtil::list_dir(LOG_PATH, '日志');
        return view('runlog', ['data' => json_encode($data['nodes'])]);
    }

    public function token()
    {
        $length = 10;
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $map['Access Key'] = substr($access, 0, $length);
        $map['Js Api'] = substr(WxHanbj::jsapi($access), 0, $length);
        $map['Ticket Api'] = substr(WxHanbj::ticketapi($access), 0, $length);
        $map['会费增加积分'] = BonusOper::FEE;
        $map['志愿者增加积分'] = BonusOper::VOLUNTEER;
        $map['活动增加积分'] = BonusOper::ACT;
        $map['活动预置名称'] = BonusOper::ACT_NAME;
        $res = Db::table('member')
            ->where(['unique_name' => ['in', BonusOper::getWorkers()]])
            ->field('tieba_id as t')
            ->select();
        $data = [];
        foreach ($res as $item) {
            $data[] = $item['t'];
        }
        $map['当前工作人员'] = implode('，', $data);
        $res = Db::table('member')
            ->where(['unique_name' => ['in', UserOper::reg()]])
            ->field('tieba_id as t')
            ->select();
        $data = [];
        foreach ($res as $item) {
            $data[] = $item['t'];
        }
        $map['内网权限'] = implode('，', $data);
        $map['当前吧务组'] = '第' . WxOrg::year . '届';
        $tables = Db::query('SHOW TABLES;');
        $Tables_in_hanbj = [];
        foreach ($tables as $item) {
            $tmp = $item['Tables_in_hanbj'];
            $Tables_in_hanbj[] = $tmp;
            $tabledesc = Db::query("DESC `hanbj`.`$tmp`");
            $tablename = [];
            foreach ($tabledesc as $item2) {
                $tablename[] = $item2['Field'];
            }
            $map["Table $tmp"] = implode(', ', $tablename);
        }
        $map['Tables'] = implode(', ', $Tables_in_hanbj);
        return view('token', ['data' => $map]);
    }

    public function test()
    {
        $org = new WxOrg();
        $ans = $org->getAns();
        $ratio = count($ans) * 100.0 / count($org->getAll()) / count($org->obj);
        $ratio = number_format($ratio, 2, '.', '');
        $miss = cache(WxOrg::name . 'getAns.miss');
        $map['unique_name'] = ['in', $org->obj];
        $ret = Db::table('member')
            ->where($map)
            ->field([
                'unique_name as u',
                'tieba_id as t'
            ])
            ->select();
        $dict = [];
        foreach ($ret as $t) {
            $dict[$t['u']] = $t['t'];
        }
        $ans = [
            'avg' => $org->getAvg($ans),
            'cmt' => $org->getComment($ans),
            'obj' => $org->obj,
            'trn' => $dict,
            'mis' => $miss,
            'rto' => $ratio
        ];
        return view('test', ['data' => json_encode($ans)]);
    }

    public function json_order()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
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
                'f.time as i',
                'm.code'
            ])
            ->select();
        $data['rows'] = $res;
        $total = Db::table('order')
            ->alias('f')
            ->count();
        $data['total'] = $total;
        return json($data);
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