<?php

namespace app\hanbj\controller;

use Exception;
use think\Controller;
use hanbj\FameOper;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\response\Json;
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

    public function log()
    {
        return view('log', ['fixed_year' => HBConfig::YEAR]);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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
                'm.code',
                'f.type'
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
        $idx = 0;
        while ($idx <= count($data)) {
            $idx += 1;
            FameOper::insertInWhile($data);
        }
        trace("Fame Add " . json_encode($data), MysqlLog::ERROR);
        return json(['msg' => '多次尝试失败，请联系神棍：' . $idx], 400);
    }

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
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
        $map_for_log = ['id' => $pk, 'unique_name' => ['neq', $unique]];
        if ($unique === HBConfig::CODER) {
            unset($map_for_log['unique_name']);
        }
        $ret = Db::table('fame')
            ->where($map_for_log)
            ->field(['year', 'grade', 'label'])
            ->find();
        if (null === $ret) {
            return json(['msg' => "查无此人 $pk"], 400);
        }
        FameOper::assertEditRight($ret['year'], $ret['grade'], $ret['label']);
        switch ($name) {
            case 'grade':
                FameOper::assertEditRight($ret['year'], $value, $ret['label']);
                break;
            case 'label':
                FameOper::assertEditRight($ret['year'], $ret['grade'], $value);
                break;
            case 'type':
                break;
            default:
                return json(['msg' => "查无此人 $name"], 400);
        }
        try {
            Db::table('fame')
                ->data([$name => $value])
                ->where($map_for_log)
                ->update();
            trace("Fame Edit $unique $pk $name $value " . json_encode($ret), MysqlLog::INFO);
        } catch (Exception $e) {
            $e = $e->getMessage();
            trace("Fame Edit $e", MysqlLog::ERROR);
            preg_match('/Duplicate entry \'(.*)-(.*)-(.*)\' for key \'year_uniq\'/', $e, $token);
            if (isset($token[3])) {
                $e = "错误！【 {$token[2]} 】已经被登记在第【 {$token[1]} 】届吧务组【 {$token[3]} 】部门中了。";
                throw new HttpResponseException(json(['msg' => '' . $e], 400));
            }
            preg_match('/Duplicate entry \'(.*)-(.*)-(.*)\' for key \'type_uniq\'/', $e, $token);
            if (isset($token[3])) {
                $e = "错误！【 {$token[2]} 】的主职务岗位已经被登记在第【 {$token[1]} 】届吧务组中了。请尝试登记兼职岗位，重试。";
                throw new HttpResponseException(json(['msg' => '' . $e], 400));
            }
            return json(['msg' => $e], 400);
        }
        return json('修改成功！');
    }
}