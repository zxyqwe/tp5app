<?php

namespace hanbj\vote;

use DateTimeImmutable;
use Exception;
use hanbj\HBConfig;
use hanbj\MemberOper;
use hanbj\TodoOper;
use hanbj\vote\quest\WxQDep;
use hanbj\vote\quest\WxQTop;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use hanbj\weixin\WxHanbj;
use util\MysqlLog;

class WxOrg
{
    const vote_cart = [1, 2];
    /**
     * @var int
     */
    private $max_score;
    /**
     * @var array
     */
    public $test;
    /**
     * @var string
     */
    public $name;
    /**
     * @var array
     */
    public $obj;
    /**
     * @var array
     */
    private $lower;
    /**
     * @var array
     */
    private $upper;
    private $catg;
    /**
     * @var DateTimeImmutable|false
     */
    private $deadline;
    /**
     * @var DateTimeImmutable
     */
    private $now;
    /**
     * @var DateTimeImmutable|false
     */
    private $start_time;

    function __construct($catg)
    {
        switch ($catg) {
            case 2:
                $quest = new WxQDep();
                break;
            case 1:
                $quest = new WxQTop();
                break;
            default:
                $res = json(['msg' => '投票分类错误'], 400);
                throw new HttpResponseException($res);
                break;
        }
        $this->catg = $catg;
        $this->upper = $quest->upper;
        $this->lower = $quest->lower;
        $this->obj = $quest->obj;
        $this->name = $quest->name;
        $this->test = $quest->test;
        $this->max_score = $quest->max_score;
        $this->start_time = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2019-10-20 20:00:00");
        $this->deadline = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2019-12-20 20:00:00");
        $this->now = new DateTimeImmutable();
    }

    public function getAll()
    {
        return array_merge($this->upper, $this->lower);
    }

    public function getUser()
    {
        return array_merge($this->getAll(), [HBConfig::CODER]);
    }

    /**
     * @throws
     */
    public function getAns()
    {
        $user = $this->getAll();
        $data = [];
        $miss = [];

        $ans_list = [];
        $ans = Db::table('score')
            ->where([
                'year' => HBConfig::YEAR,
                'catg' => $this->catg,
                'name' => ['in', $this->obj]
            ])
            ->field([
                'ans',
                'unique_name',
                'name'
            ])
            ->select();
        foreach ($ans as $item) {
            $ans_list[$item['unique_name'] . $item['name']] = $item['ans'];
        }

        foreach ($user as $u) {
            foreach ($this->obj as $o) {
                if (isset($ans_list[$u . $o])) {
                    $data[] = [
                        'ans' => json_decode($ans_list[$u . $o], true),
                        'u' => $u,
                        'o' => $o,
                        'w' => in_array($u, $this->upper) ? 2.0 : 1.0
                    ];
                } else {
                    $miss[] = $u;
                }
            }
        }
        $miss = array_unique($miss);
        cache($this->name . 'getAns.miss_real', implode(',', $miss));
        if (session("unique_name") != HBConfig::CODER && count($miss) * 3 > count($user)) {
            $miss = ['秘密（' . count($miss) . '/' . count($user) . '）'];
        } else {
            $ret = MemberOper::get_tieba($miss);
            $miss = [];
            foreach ($ret as $item) {
                $miss[] = "{$item['u']}~{$item['t']}";
            }
        }
        $miss = implode(', ', $miss);
        cache($this->name . 'getAns.miss', $miss);
        return $data;
    }

    public function getAvg($data)
    {
        $cnt = [];
        foreach ($this->obj as $o) {
            $cnt[$o] = 0;
        }
        $ans = [];
        foreach ($data as $item) {
            $weight = $item['w'];
            $cnt[$item['o']] += $weight;
            for ($i = 0; $i < count($this->test); $i++) {
                if (!isset($ans[$item['o']])) {
                    $ans[$item['o']] = [];
                }
                if (!isset($ans[$item['o']][$i])) {
                    $ans[$item['o']][$i] = 0;
                }
                $ans[$item['o']][$i] += $weight * intval($item['ans']['sel'][$i]);
            }
        }

        $ret = [];
        for ($i = 0; $i < count($this->test); $i++) {
            $test = $this->test[$i];
            if (!isset($test['a'])) {
                continue;
            }
            $tmp = ['q' => $test['q']];
            foreach ($this->obj as $o) {
                if (isset($ans[$o])) {
                    $ans[$o][$i] /= $cnt[$o];
                    $ans[$o][$i] = number_format($ans[$o][$i], 2, '.', '');
                    $tmp[$o] = $ans[$o][$i];
                }
            }
            $ret[] = $tmp;
        }
        $tmp = ['q' => "总分（{$this->max_score}分）"];
        foreach ($this->obj as $o) {
            if (isset($ans[$o])) {
                $tmp[$o] = number_format(array_sum($ans[$o]), 2, '.', '');
            }
        }
        $ret[] = $tmp;
        return $ret;
    }

    public function getComment($data)
    {
        $ret = [];
        foreach ($data as $item) {
            if (!isset($item['ans']['sel_add'])) {
                $item['ans']['sel_add'] = [];
            }
            $cmt = $item['ans']['sel_add'];
            $sel = $item['ans']['sel'];
            for ($i = 0; $i < count($cmt); $i++) {
                if (empty($cmt[$i]))
                    continue;
                $tmp = $this->test[$i];
                $s = 10;
                if (isset($tmp['s'])) {
                    $s = $tmp['s'];
                }
                $i_score = intval($sel[$i]);
                if ($i_score < $s * 0.6 || $i_score === $s) {
                    $ret[] = [
                        'q' => $tmp['q'],
                        'o' => $item['o'],
                        't' => $cmt[$i],
                        's' => $sel[$i]
                    ];
                }

            }
        }
        return $ret;
    }

    /**
     * @throws
     */
    private function progress()
    {
        $all = $this->getAll();
        $len = count($all) * count($this->obj);

        $map = [
            'year' => HBConfig::YEAR,
            'catg' => $this->catg,
            'name' => ['in', $this->obj],
            'unique_name' => ['in', $this->getAll()]
        ];
        $acc = Db::table('score')
            ->where($map)
            ->count('id');

        $res = $acc * 100.0 / $len;
        return "投票数量...... $acc / $len\n总进度...... " . round($res, 2) . "%\n";
    }

    public function checkAns(&$ans)
    {
        if (!is_array($ans)) {
            throw new HttpResponseException(json(['msg' => '答案类型不匹配！'], 400));
        }
        $len = count($this->test);
        if (!isset($ans['sel']) || !is_array($ans['sel']) || count($ans['sel']) !== $len) {
            throw new HttpResponseException(json(['msg' => '没有答案啊？'], 400));
        }
        if (array_sum($ans['sel']) === $this->max_score) {
            throw new HttpResponseException(json(['msg' => '不能给满分！'], 400));
        }
        if (!isset($ans['sel_add'])) {
            $ans['sel_add'] = [];
        }
        if (!is_array($ans['sel_add'])) {
            throw new HttpResponseException(json(['msg' => '文字类型不匹配！'], 400));
        }
        foreach (range(0, $len - 1) as $i) {
            $tmp = $this->test[$i];
            if (!isset($tmp['a'])) {
                continue;
            }
            $s = 10;
            if (isset($tmp['s'])) {
                $s = $tmp['s'];
            }
            $i_score = intval($ans['sel'][$i]);
            if (($i_score < $s * 0.6 || $i_score === $s)
            ) {
                if (!isset($ans['sel_add'][$i])) {
                    throw new HttpResponseException(json(['msg' => "{$tmp['q']}没有文字！"], 400));
                }
                $tmp_count = strlen($ans['sel_add'][$i]);
                if ($tmp_count < 15) {
                    throw new HttpResponseException(json(['msg' => "{$tmp['q']}文字数量不对 $tmp_count ！ {$ans['sel_add'][$i]}"], 400));
                }
            }
        }
    }

    /**
     * @param $uname
     * @return string
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function listobj($uname)
    {
        $ret = "\n\n提取投票......{$this->name}";
        if (!in_array($uname, $this->getUser())) {
            return "$ret\n身份验证......失败\n";
        }
        if ($this->now > $this->deadline) {
            return '投票已关闭';
        } elseif ($this->now < $this->start_time) {
            return '投票未开始';
        }
        $ret = "$ret\n身份验证......成功";

        $rest_time = $this->deadline->diff($this->now);
        $rest_time = $rest_time->format("%a 天 %H 时 %i 分 %s 秒");
        $ret = "$ret\n投票倒计时......$rest_time";

        $prog = $this->progress();
        $ret = "$ret\n链接有效期......一小时（过时重新取号）\n$prog";
        $finish = '';
        $unfinish = '';
        $sep = "---------------\n";

        $ans_list = [];
        $ans = Db::table('score')
            ->where([
                'year' => HBConfig::YEAR,
                'catg' => $this->catg,
                'unique_name' => $uname
            ])
            ->field('name')
            ->select();
        foreach ($ans as $item) {
            $ans_list[] = $item['name'];
        }

        foreach ($this->obj as $item) {
            $nonce = WxHanbj::setJump('wxtest', "$item-{$this->catg}", $uname, 60 * 60);
            if (!in_array($item, $ans_list)) {
                $unfinish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">$item</a>\n";
            } else {
                $finish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">已完成-$item</a>\n";
            }
        }
        return $ret . $sep . $unfinish . $sep . $finish . $sep;
    }

    public function addAns($uname, $obj, $ans)
    {
        try {
            $ret = Db::table('score')
                ->where([
                    'unique_name' => $uname,
                    'name' => $obj,
                    'year' => HBConfig::YEAR,
                    'catg' => $this->catg
                ])
                ->data(['ans' => $ans])
                ->update();
            if ($ret <= 0) {
                Db::table('score')
                    ->data([
                        'ans' => $ans,
                        'unique_name' => $uname,
                        'name' => $obj,
                        'year' => HBConfig::YEAR,
                        'catg' => $this->catg
                    ])
                    ->insert();
                trace("投票add $uname {$this->catg} $obj", MysqlLog::INFO);
            } else {
                trace("投票update $uname {$this->catg} $obj", MysqlLog::INFO);
            }
            $id_key = $this->calc_int($obj, $uname);
            if (!TodoOper::TestTypeKeyValid(TodoOper::VOTE_ORG, $id_key)) {
                TodoOper::handleTodo(TodoOper::VOTE_ORG, $id_key, TodoOper::DONE);
            }
        } catch (Exception $e) {
            $e = $e->getMessage();
            preg_match('/Duplicate entry \'(.*)-(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                return json(['msg' => 'OK']);
            }
            trace("Test UP $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
        return json(['msg' => 'OK']);
    }

    /**
     * @param string $target
     * @param string $uname
     * @return int
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function calc_int($target, $uname)
    {
        $id_ret = Db::table("member")
            ->where(["unique_name" => $uname])
            ->field(['id'])
            ->cache(86400 * 30)
            ->find();
        if (false === $id_ret) {
            throw new HttpResponseException(json(['msg' => "calc_int $target, $uname"], 400));
        }
        $id_ret = intval($id_ret['id']);
        $id_target = intval(array_search($target, $this->obj, true));
        $id_catg = intval($this->catg);
        return $id_target + $id_catg * 100 + $id_ret * 10000;
    }

    /**
     * @throws
     */
    public function try_add_todo()
    {
        $cache_key = $this->name . "try_add_todo";
        if (cache("?$cache_key")) {
            return;
        }
        cache($cache_key, $cache_key, 3600);

        $todo_uname = "" . cache($this->name . 'getAns.miss_real');
        $todo_uname = explode(",", $todo_uname);
        if (count($todo_uname) == 0) {
            return;
        }
        foreach ($todo_uname as $uname) {
            foreach ($this->obj as $target) {
                $key = $this->calc_int($target, $uname);
                if (!TodoOper::TestTypeKeyValid(TodoOper::VOTE_ORG, $key)) {
                    continue;
                }
                TodoOper::RecvTodoFromOtherOper(
                    TodoOper::VOTE_ORG,
                    $key,
                    json_encode([
                        "name" => $this->name,
                        "target" => $target
                    ]),
                    $uname);
            }
        }
    }
}
