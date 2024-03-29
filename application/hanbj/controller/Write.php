<?php

namespace app\hanbj\controller;

use bilibili\BiliBase;
use Exception;
use hanbj\ActivityOper;
use hanbj\BonusOper;
use hanbj\ClubOper;
use hanbj\FeeOper;
use hanbj\MemberOper;
use hanbj\HBConfig;
use think\Controller;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\response\Json;
use util\MysqlLog;
use util\TableOper;


class Write extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/write_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function volunteer()
    {
        $data['name'] = BonusOper::getActName();
        $data['act'] = BonusOper::getActBonus();
        $data['vol'] = BonusOper::getVolBonus();
        return view('volunteer', ['data' => $data]);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function fee_search()
    {
        $name = input('post.name');
        if (empty($name)) {
            return json();
        }
        $map['tieba_id|unique_name'] = ['like', '%' . $name . '%'];
        $map['code'] = ['in', array_merge(MemberOper::getAllReal(), [MemberOper::JUNIOR])];
        $tmp = Db::table('member')
            ->alias('f')
            ->where($map)
            ->order('f.id')
            ->limit(10)
            ->field([
                'f.tieba_id as t',
                'f.unique_name as u',
                'f.code as c'
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
        $type = $type === FeeOper::ADD ? 1 : -1;
        $data = [];
        $oper = session('name');
        $d = date("Y-m-d H:i:s");
        foreach ($name as $tmp) {
            $data[] = [
                'unique_name' => $tmp['u'],
                'oper' => $oper,
                'code' => $type,
                'fee_time' => $d,
                'bonus' => $type * BonusOper::getFeeBonus()
            ];
            FeeOper::uncache($tmp['u']);
        }
        Db::startTrans();
        try {
            $res = Db::table('nfee')
                ->insertAll($data);
            if ($res === count($data)) {
                Db::commit();
                trace("Fee " . json_encode($name), MysqlLog::INFO);
            } else {
                Db::rollback();
                return json(['msg' => $res], 400);
            }
        } catch (Exception $e) {
            Db::rollback();
            $e = $e->getMessage();
            trace("Fee Add $e", MysqlLog::ERROR);
            return json(['msg' => $e], 400);
        }
        return json(['msg' => 'ok']);
    }

    public function vol_add()
    {
        $name = input('post.name/a', []);
        if (empty($name)) {
            return json(['msg' => 'empty name'], 400);
        }
        foreach ($name as $tmp) {
            ActivityOper::signAct($tmp['u'], '', BonusOper::getActName(), BonusOper::getVolBonus(), null, true);
        }
        return json(['msg' => 'ok']);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function json_create()
    {
        switch ($this->request->method()) {
            case 'GET':
                $name = input('get.name');
                if (empty($name)) {
                    return json();
                }
                $map['tieba_id|unique_name'] = ['like', '%' . $name . '%'];
                $map['code'] = MemberOper::JUNIOR;
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
            case 'POST':
                $uni = input('post.uni');
                $tie = input('post.tie');
                $gender = input('post.gender');
                $phone = input('post.phone', 0, FILTER_VALIDATE_INT);
                $QQ = input('post.QQ', 0, FILTER_VALIDATE_INT);
                $eid = input('post.eid');
                $rn = input('post.rn');
                $mail = input('post.mail', 0, FILTER_VALIDATE_EMAIL);
                $ret = session('unique_name');
                $ret = MemberOper::Junior2Normal($uni, $tie, $gender, $phone, $QQ, $ret, $eid, $rn, $mail);
                if ($ret) {
                    return json(['msg' => 'ok']);
                }
                return json(['msg' => '失败，可能已经添加了？'], 400);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    public function json_token()
    {
        switch ($this->request->method()) {
            case 'GET':
                $settings = [
                    [
                        'name' => '活动预置名称',
                        'key' => '_ACT_NAME',
                        'value' => BonusOper::getActName(false),
                        'addon' => date('Y')
                    ],
                    [
                        'name' => '志愿者增加积分',
                        'key' => '_VOLUNTEER',
                        'value' => BonusOper::getVolBonus()
                    ],
                    [
                        'name' => '活动增加积分',
                        'key' => '_ACT_BONUS',
                        'value' => BonusOper::getActBonus()
                    ],
                    [
                        'name' => '会费增加积分',
                        'key' => '_FEE_BONUS',
                        'value' => BonusOper::getFeeBonus()
                    ]
                ];
                if (session('unique_name') === HBConfig::CODER) {
                    $settings[] = [
                        'name' => 'BiliBili Cookies',
                        'key' => '_BILI_COOKIES',
                        'value' => BiliBase::getCookies()
                    ];
                }
                return json($settings);
            case 'POST':
                $key = input('post.key');
                $value = input('post.value');
                $unique = session('unique_name');
                trace("$unique $key $value", MysqlLog::INFO);
                switch ($key) {
                    case '_ACT_NAME':
                        cache('BonusOper::ACT_NAME', $value);
                        return json(['msg' => 'ok']);
                    case '_VOLUNTEER':
                        cache('BonusOper::VOLUNTEER', intval($value));
                        return json(['msg' => 'ok']);
                    case '_ACT_BONUS':
                        cache('BonusOper::ACT_BONUS', intval($value));
                        return json(['msg' => 'ok']);
                    case '_FEE_BONUS':
                        cache('BonusOper::FEE_BONUS', intval($value));
                        return json(['msg' => 'ok']);
                    case '_BILI_COOKIES':
                        if (session('unique_name') === HBConfig::CODER) {
                            BiliBase::getCookies($value);
                            cache("bilibilineedlogin", null);
                        }
                        return json(['msg' => 'ok']);
                    default:
                        return json(['msg' => $key], 400);
                }
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function edit_prom()
    {
        switch ($this->request->method()) {
            case 'GET':
                $ret = Db::table('prom')
                    ->field([
                        'id', 'name', 'img', 'desc', 'info', 'show', 'master', 'seq'
                    ])
                    ->order('show, seq')
                    ->select();
                return json($ret);
            case 'POST':
                $name = input('post.name');
                $pk = intval(input('post.pk'));
                $value = input('post.value');
                $unique = session('unique_name');
                if (strlen($name) < 1) {
                    return json(['msg' => 'name len short'], 400);
                }
                TableOper::generateOneTable('prom');
                TableOper::assertInField('prom', $name);
                try {
                    if ($pk > 0) {
                        Db::table('prom')
                            ->data([$name => $value])
                            ->where(['id' => $pk])
                            ->update();
                        trace("Prom Edit $unique $pk $name $value", MysqlLog::INFO);
                    } else {
                        Db::table('prom')
                            ->data(['name' => $name])
                            ->insert();
                        trace("Prom Add $unique $name", MysqlLog::INFO);
                    }
                } catch (Exception $e) {
                    $e = $e->getMessage();
                    trace("Prom Edit $e", MysqlLog::ERROR);
                    return json(['msg' => $e], 400);
                }
                return json('修改成功！');
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    public function edit_club()
    {
        $pk = intval(input('post.pk'));
        $value = intval(input('post.value'));
        return ClubOper::grantClub($pk, $value);
    }

    /**
     * @throws
     */
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

}
