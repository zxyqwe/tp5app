<?php

namespace app\hanbj\controller;

use hanbj\BonusOper;
use hanbj\FeeOper;
use hanbj\MemberOper;
use hanbj\UserOper;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;


class Write extends Controller
{
    protected $beforeActionList = [
        'valid_id'
    ];

    protected function valid_id()
    {
        UserOper::valid_pc(true);
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/write_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, '页面不存在', [$action]);
    }

    public function volunteer()
    {
        $data['name'] = BonusOper::ACT_NAME;
        $data['act'] = BonusOper::ACT;
        $data['vol'] = BonusOper::VOLUNTEER;
        return view('volunteer', ['data' => $data]);
    }

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

    public function vol_add()
    {
        $name = input('post.name/a', []);
        if (empty($name)) {
            return json(['msg' => 'empty name'], 400);
        }
        $data = [];
        $oper = session('name');
        $d = date("Y-m-d H:i:s");
        foreach ($name as $tmp) {
            $data[] = [
                'unique_name' => $tmp['u'],
                'oper' => $oper,
                'act_time' => $d,
                'bonus' => BonusOper::VOLUNTEER,
                'name' => BonusOper::ACT_NAME . '志愿者'
            ];
        }
        Db::startTrans();
        try {
            $res = Db::table('activity')
                ->insertAll($data);
            if ($res === count($data)) {
                Db::commit();
            } else {
                Db::rollback();
                return json(['msg' => $res], 400);
            }
        } catch (\Exception $e) {
            Db::rollback();
            $e = $e->__toString();
            preg_match('/Duplicate entry \'(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                $e = "错误！【 {$token[2]} 】已经被登记在【 {$token[1]} 】活动中了。请删除此项，重试。";
            }
            return json(['msg' => $e], 400);
        }
        return json(['msg' => 'ok']);
    }

    public function fame_add()
    {
        $name = input('post.name/a', []);
        if (empty($name)) {
            return json(['msg' => 'empty name'], 400);
        }
        $year = input('post.year', 1, FILTER_VALIDATE_INT);
        $grade = input('post.grade', 0, FILTER_VALIDATE_INT);
        $label = input('post.label', '');
        if (empty($label) || $year < 1 || $year > 12 || $grade < 0 || $grade > 4) {
            return json(['msg' => '参数错误'], 400);
        }
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
            $e = $e->__toString();
            preg_match('/Duplicate entry \'(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                $e = "错误！【 {$token[2]} 】已经被登记在第【 {$token[1]} 】届吧务组中了。请删除此项，重试。";
            }
            return json(['msg' => '' . $e], 400);
        }
        return json(['msg' => 'ok']);
    }

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
                return json(['msg' => '失败'], 400);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }
}
