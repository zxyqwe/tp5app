<?php

namespace app\hanbj\controller;

use think\Controller;
use hanbj\FameOper;
use think\Db;
use think\exception\HttpResponseException;
use util\MysqlLog;
use util\TableOper;
use hanbj\HBConfig;

class Fame extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/fame_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function json_init()
    {
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $res = Db::table('fame')
            ->alias('f')
            ->join($join)
            ->field([
                'f.id',
                'f.unique_name as u',
                'tieba_id as t',
                'year as y',
                'grade',
                'label',
                'm.code'
            ])
            ->select();
        $res = FameOper::sort($res);
        return json($res);
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
        if (empty($label) || $year < HBConfig::YEAR || $year > HBConfig::YEAR + 1 || $grade < 0 || $grade > FameOper::max_pos) {
            return json(['msg' => '参数错误'], 400);
        }
        $data = [];
        foreach ($name as $tmp) {
            FameOper::assertEditRight($year, $grade, $label);
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
                trace("Fame " . json_encode($name), MysqlLog::INFO);
            } else {
                Db::rollback();
                return json(['msg' => $res], 400);
            }
        } catch (\Exception $e) {
            Db::rollback();
            $e = $e->getMessage();
            trace("Fame Add $e", MysqlLog::ERROR);
            preg_match('/Duplicate entry \'(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                $e = "错误！【 {$token[2]} 】已经被登记在第【 {$token[1]} 】届吧务组中了。请删除此项，重试。";
            }
            return json(['msg' => '' . $e], 400);
        }
        return json(['msg' => 'ok']);
    }

    public function edit_fame()
    {
        $name = input('post.name');
        $pk = intval(input('post.pk'));
        $value = input('post.value');
        $unique = session('unique_name');
        if (strlen($name) < 1) {
            return json(['msg' => 'name len short'], 400);
        }
        TableOper::generateOneTable('fame');
        TableOper::assertInField('fame', $name);
        $ret = Db::table('fame')
            ->where(['id' => $pk, 'unique_name' => ['neq', $unique]])
            ->field(['year', 'grade', 'label'])
            ->find();
        if (null === $ret) {
            return json(['msg' => "查无此人 $pk"], 400);
        }
        FameOper::assertEditRight($ret['year'], $ret['grade'], $ret['label']);
        switch ($name) {
            case'grade':
                FameOper::assertEditRight($ret['year'], $value, $ret['label']);
                break;
            case'label':
                FameOper::assertEditRight($ret['year'], $ret['grade'], $value);
                break;
            default:
                return json(['msg' => "查无此人 $name"], 400);
        }
        try {
            Db::table('fame')
                ->data([$name => $value])
                ->where(['id' => $pk, 'unique_name' => ['neq', $unique]])
                ->update();
            trace("Fame Edit $unique $pk $name $value", MysqlLog::INFO);
        } catch (\Exception $e) {
            $e = $e->getMessage();
            trace("Fame Edit $e", MysqlLog::ERROR);
            return json(['msg' => $e], 400);
        }
        return json('修改成功！');
    }

}