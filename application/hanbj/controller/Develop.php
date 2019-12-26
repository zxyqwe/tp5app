<?php

namespace app\hanbj\controller;

use hanbj\UserOper;
use hanbj\BonusOper;
use hanbj\HBConfig;
use hanbj\MemberOper;
use hanbj\weixin\WxHanbj;
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

    public function token()
    {
        $length = 10;
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $map['Access Key'] = substr($access, 0, $length);
        $map['Js Api'] = substr(WxHanbj::jsapi(), 0, $length);
        $map['Ticket Api'] = substr(WxHanbj::ticketapi(), 0, $length);
        $map['会费增加积分'] = BonusOper::getFeeBonus();
        $map['志愿者增加积分'] = BonusOper::getVolBonus();
        $map['活动增加积分'] = BonusOper::getActBonus();
        $map['活动预置名称'] = BonusOper::getActName();

        $res = MemberOper::get_tieba(BonusOper::getWorkers());
        $map['当前微信工作人员'] = implode('，', MemberOper::pretty_tieba($res));

        $res = MemberOper::get_tieba(UserOper::reg());
        $map['内网登录权限'] = implode('，', MemberOper::pretty_tieba($res));

        $map['内网超级权限'] = implode('，', UserOper::pretty_toplist());

        $map['当前吧务组'] = '第' . HBConfig::YEAR . '届';
        return view('token', ['data' => $map]);
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
//        $ret = '';
//        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
//        $ret = WxHanbj::addUnionID($access);
        $ret = MemberOper::create_unique_unused();
//        $ret = ActivityOper::revokeTest();
//        $ret = StatOper::generateOneDay(StatOper::LOG_NUM);
//        WxHanbj::setMenu();

        return json(['msg' => $ret]);
    }
}
