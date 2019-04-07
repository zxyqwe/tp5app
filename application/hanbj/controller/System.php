<?php

namespace app\hanbj\controller;

use hanbj\UserOper;
use think\Controller;
use think\Db;
use hanbj\BonusOper;
use hanbj\HBConfig;
use hanbj\MemberOper;
use hanbj\weixin\WxHanbj;
use hanbj\vote\WxOrg;
use think\exception\HttpResponseException;
use util\MysqlLog;

class System extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'server']
    ];

    protected function valid_id()
    {
        UserOper::valid_pc($this->request->isAjax());
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/system_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function runlog()
    {
        define('TAG_TIMEOUT_EXCEPTION', true);
        MemberOper::daily();
        if (session('name') !== HBConfig::CODER) {
            return redirect('https://app.zxyqwe.com/hanbj/index/home');
        }

        switch ($this->request->method()) {
            case 'GET':
                return view('runlog');
            case 'POST':
                $size = input('post.limit', 20, FILTER_VALIDATE_INT);
                $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
                $size = min(1000, max(0, $size));
                $offset = max(0, $offset);
                $level = MysqlLog::get_level(input("post.level"));
                $tmp = Db::table('logs')
                    ->where(['type' => ['in', $level]])
                    ->limit($offset, $size)
                    ->order('id', 'desc')
                    ->field([
                        'id',
                        'ip',
                        'time',
                        'method',
                        'url',
                        'query',
                        'type',
                        'msg'
                    ])
                    ->select();
                $data['rows'] = $tmp;
                $total = Db::table('logs')
                    ->where(['type' => ['in', $level]])
                    ->count();
                $data['total'] = $total;
                return json($data);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    public function token()
    {
        $length = 10;
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $map['Access Key'] = substr($access, 0, $length);
        $map['Js Api'] = substr(WxHanbj::jsapi($access), 0, $length);
        $map['Ticket Api'] = substr(WxHanbj::ticketapi($access), 0, $length);
        $map['会费增加积分'] = BonusOper::getFeeBonus();
        $map['志愿者增加积分'] = BonusOper::getVolBonus();
        $map['活动增加积分'] = BonusOper::getActBonus();
        $map['活动预置名称'] = BonusOper::getActName();
        $res = MemberOper::get_tieba(BonusOper::getWorkers());
        $data = [];
        foreach ($res as $item) {
            $data[] = $item['u'] . '~' . $item['t'];
        }
        $map['当前工作人员'] = implode('，', $data);
        $res = MemberOper::get_tieba(UserOper::reg());
        $data = [];
        foreach ($res as $item) {
            $data[] = $item['u'] . '~' . $item['t'];
        }
        $map['内网权限'] = implode('，', $data);
        $map['当前吧务组'] = '第' . HBConfig::YEAR . '届';
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
        $ret = [];
        foreach (WxOrg::vote_cart as $catg) {
            $org = new WxOrg($catg);
            $ans = $org->getAns();
            $ratio = count($ans) * 100.0 / count($org->getAll()) / count($org->obj);
            $ratio = number_format($ratio, 2, '.', '');
            $miss = cache($org->name . 'getAns.miss');
            $ret[] = [
                'avg' => $org->getAvg($ans),
                'cmt' => $org->getComment($ans),
                'obj' => $org->obj,
                'mis' => $miss,
                'rto' => $ratio,
                'all' => implode(', ', $org->getAll()),
                'name' => $org->name
            ];
        }
        return view('test', ['data' => json_encode($ret)]);
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

    public function json_club()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $join = [
            ['member m', 'm.unique_name=f.owner', 'left'],
            ['member n', 'n.unique_name=f.worker', 'left']
        ];
        $res = Db::table('club')
            ->alias('f')
            ->join($join)
            ->order('f.id', 'desc')
            ->limit($offset, $size)
            ->field([
                'f.id',
                'f.owner',
                'm.tieba_id as m',
                'n.tieba_id as n',
                'f.worker',
                'f.start_time',
                'f.name',
                'f.stop_time',
                'f.code'
            ])
            ->select();
        $data['rows'] = $res;
        $total = Db::table('club')
            ->alias('f')
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function server()
    {
        switch ($this->request->method()) {
            case 'GET':
                UserOper::valid_pc($this->request->isAjax());
                if (session('name') !== HBConfig::CODER) {
                    return redirect('https://app.zxyqwe.com/hanbj/index/home');
                }
                $module = input("get.module");
                $module = "linux-dash-cache-$module";
                if (cache("?$module")) {
                    return json(json_decode(cache($module), true));
                }
                break;
            case 'POST':
                local_cron();
                $value = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
                foreach ($value as $key => $v) {
                    cache("linux-dash-cache-$key", json_encode($v));
                }
                return json(['msg' => 'ok']);
        }
        return json([
            'success' => false,
            'status' => "Invalid module"
        ], 404);
    }
}
