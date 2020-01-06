<?php

namespace app\hanbj\controller;

use hanbj\CardOper;
use hanbj\UserOper;
use hanbj\HBConfig;
use hanbj\MemberOper;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\response\Json;
use think\response\Redirect;
use think\response\View;
use util\MysqlLog;
use think\Db;
use think\Controller;
use think\exception\HttpResponseException;
use util\StatOper;
use util\TableOper;

class Develop extends Controller
{
    protected $beforeActionList = [
        'coder',
    ];

    protected function coder()
    {
        if (request()->ip() === config('local_mech')) {
            return;
        }
        UserOper::valid_pc($this->request->isAjax());
        if (session('name') === HBConfig::CODER) {
            return;
        }
        if (request()->isAjax()) {
            $res = json(['msg' => '没有权限'], 400);
        } else {
            $res = redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        throw new HttpResponseException($res);
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/develop_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    /**
     * @return Json|View
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public function runlog()
    {
        define('TAG_TIMEOUT_EXCEPTION', true);
        MemberOper::daily();

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

    public function logdata()
    {
        switch ($this->request->method()) {
            case 'GET':
                return view('logdata');
            case 'POST':
                return StatOper::OutputAll(StatOper::LOG_NUM);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    /**
     * @return Json
     * @throws \HttpResponseException
     */
    public function server()
    {
        switch ($this->request->method()) {
            case 'GET':
                $module = input("get.module");
                $module = "linux-dash-cache-$module";
                if (cache("?$module")) {
                    return json(json_decode(cache($module), true));
                }
                break;
            case 'POST':
                local_cron();
                $value = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
                $report_module = [];
                foreach ($value as $key => $v) {
                    $report_module[] = $key;
                    cache("linux-dash-cache-$key", json_encode($v));
                }
                return json(['msg' => 'ok', 'data' => $report_module]);
        }
        return json([
            'success' => false,
            'status' => "Invalid module"
        ], 404);
    }

    public function table()
    {
        switch ($this->request->method()) {
            default:
            case 'GET':
                return view('table');
            case 'POST':
                $tables = Db::query('SHOW TABLES;');
                $Tables_in_hanbj = [];
                foreach ($tables as $item) {
                    $tmp = $item['Tables_in_hanbj'];
                    $Tables_in_hanbj[] = TableOper::generateOneTable($tmp);
                }
                return json($Tables_in_hanbj);
        }
    }

    /**
     * @param string $obj
     * @return Json|Redirect|View
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function tableone($obj = '')
    {
        switch ($this->request->method()) {
            default:
            case 'GET':
                if (empty($obj) || !TableOper::hasGenerated($obj)) {
                    return redirect('https://app.zxyqwe.com/hanbj/develop/table');
                }
                return view('tableone', ['data' => TableOper::getFieldsStr($obj)]);
            case 'POST':
                if (empty($obj) || !TableOper::hasGenerated($obj)) {
                    return json([]);
                }
                $size = input('post.limit', 20, FILTER_VALIDATE_INT);
                $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
                $size = min(100, max(0, $size));
                $offset = max(0, $offset);
                $res = Db::table($obj)
                    ->field(TableOper::getFieldsArray($obj))
                    ->limit($offset, $size)
                    ->order('id desc')
                    ->select();
                $data['rows'] = $res;
                $total = Db::table($obj)
                    ->count();
                $data['total'] = $total;
                return json($data);
        }
    }

    public function debug()
    {
//        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');

//        $ret = WxHanbj::addUnionID($access);

//        $ret = MemberOper::create_unique_unused();

//        $ret = ActivityOper::revokeTest();

//        $ret = StatOper::generateOneDay(StatOper::LOG_NUM);

//        WxHanbj::setMenu();

//        $msg = Db::table('logs')
//            ->where([
//                'msg' => ['like', '%TEMPUSE UNUSED%'],
//                'time' => ['like', '2020-01-01 07:55%']
//            ])
//            ->field(['msg'])
//            ->select();
//        $sret = [];
//        foreach ($msg as $item) {
//            $sret[] = explode(' ', $item['msg'])[0];
//        }

//        CardOper::clear('oJkBfv_5BczVnTviPkdbYXWcqCoA');
//
//        $person = Db::table('member')
//            ->where([
//                'code' => ['eq', MemberOper::TEMPUSE],
//                'start_time' => ''
//            ])
//            ->field(['unique_name'])
//            ->select();
//        $u_list = [];
//        foreach ($person as $item) {
//            $u_list[] = $item['unique_name'];
//        }
//
//        $log = Db::table('logs')
//            ->where([
//                'msg' => ['like', "%UNUSED TEMPUSE%"]
//            ])
//            ->order('id desc')
//            ->field(['time', 'msg'])
//            ->select();
//        $log_map = [];
//        foreach ($log as $line) {
//            $u = explode(' ', $line['msg'])[0];
//            if (!in_array($u, $u_list)) {
//                continue;
//            }
//            if (in_array($u, $log_map)) {
//                if ($line['time'] >= $log_map[$u][0]) {
//                    return json([$line['time'], $log_map[$u][0]]);
//                }
//                continue;
//            } else {
//                $log_map[$u] = [$line['time']];
////                echo "$u<br />";
//            }
//        }
//
//        foreach ($u_list as $u) {
//            if (!array_key_exists($u, $log_map)) {
////                echo "$u\n";
//                continue;
//            }
//            if (count($log_map[$u]) !== 1) {
//                echo "$u<br />";
//                continue;
//            }
//            $start_time = $log_map[$u][0];
//            $ret = Db::table('member')
//                ->where([
//                    'unique_name' => $u,
//                    'code' => MemberOper::TEMPUSE,
//                    'start_time' => ''
//                ])
//                ->data([
//                    'start_time' => $start_time
//                ])
//                ->update();
//            echo "$u $start_time $ret<br />";
//        }
//
//        $al_ret = Db::table('member')
//            ->where(['start_time' => ['neq', '']])
//            ->field(['unique_name', 'start_time', 'code'])
//            ->select();

//        $ret = Db::table('member')
//            ->where([
//                'code' => ['in', [MemberOper::JUNIOR]],
//                'start_time' => '',
//                'year_time' => 2019
//            ])
//            ->data(['start_time' => '2019-01-01 00:00:01'])
//            ->update();

//        $first = Db::table('logs')
//            ->where(['msg' => ['like', "%UNUSED TEMPUSE%"]])
//            ->order('id')
//            ->find();

        $ret = Db::table('member')
            ->where([
                'code' => ['not in', [MemberOper::UNUSED, MemberOper::DELETED_HISTORY]],
                'start_time' => ''
            ])
            ->field([
                'unique_name', 'year_time', 'code'
            ])
            ->select();

//        $u_log = Db::table('logs')
//            ->where(['msg' => ['like', '%磬癸亥%']])
//            ->field('msg')
//            ->select();

        return json([
            'msg' => $ret,
//            'first' => $first,
//            'al_ret' => $al_ret,
//            'u_log' => $u_log
        ]);
    }
}
