<?php

namespace hanbj\vote;

use DateTimeImmutable;
use Exception;
use hanbj\FameOper;
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
use think\exception\PDOException;
use think\response\Json;
use util\MysqlLog;

class WxOrg
{
    const vote_cart = [1, 2];
    /**
     * @var WxQuest
     */
    public $quest;
    private $catg;

    function __construct($catg)
    {
        switch ($catg) {
            case 2:
                $this->quest = new WxQDep();
                break;
            case 1:
                $this->quest = new WxQTop();
                break;
            default:
                $res = json(['msg' => '投票分类错误'], 400);
                throw new HttpResponseException($res);
                break;
        }
        $this->catg = $catg;
    }

    private static function GetDeadline()
    {
        return DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2020-12-10 23:59:59");
    }

    private static function IsExpired()
    {
        $start_time = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2020-10-20 20:00:00");
        $deadline = self::GetDeadline();
        $now = new DateTimeImmutable();
        return $now < $start_time || $now > $deadline;
    }

    private static function GetRestTime()
    {
        $deadline = self::GetDeadline();
        $now = new DateTimeImmutable();
        return $deadline->diff($now);
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getAll()
    {
        $ret = [];
        $ret = array_merge($ret, $this->quest->fame_power2);
        $ret = array_merge($ret, $this->quest->fame_power1);
        $ret = array_merge($ret, $this->quest->fame_power_half);
        return FameOper::get_for_test($ret);
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUser()
    {
        return array_merge($this->getAll(), [HBConfig::CODER]);
    }

    public function trans_rules()
    {
        $idx = 0;
        $power2_rule = "";
        $power1_rule = "";
        $power_half_rule = "";
        $no_involve = "";
        while ($idx <= FameOper::max_pos) {
            if (in_array($idx, $this->quest->fame_power2)) {
                $power2_rule .= FameOper::translate($idx) . "；";
            } elseif (in_array($idx, $this->quest->fame_power1)) {
                $power1_rule .= FameOper::translate($idx) . "；";
            } elseif (in_array($idx, $this->quest->fame_power_half)) {
                $power_half_rule .= FameOper::translate($idx) . "；";
            } else {
                $no_involve .= FameOper::translate($idx) . "；";
            }
            $idx++;
        }
        return "票力2：$power2_rule\n票力1：$power1_rule\n票力0.5：$power_half_rule\n不参与：$no_involve";
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
                'name' => ['in', $this->quest->obj]
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

        $user_fame = $this->quest->fame_power2;
        $user_fame = array_merge($user_fame, $this->quest->fame_power1);
        $user_fame = array_merge($user_fame, $this->quest->fame_power_half);
        $user_label = FameOper::get_label_for_test($user_fame);
        $user_fame = [];
        foreach ($user_label as $item) {
            $user_fame[$item['u']] = $item;
        }

        foreach ($user as $u) {
            foreach ($this->quest->obj as $o) {
                if (isset($ans_list[$u . $o])) {
                    $data[] = [
                        'ans' => json_decode($ans_list[$u . $o], true),
                        'u' => $u,
                        'o' => $o,
                        'l' => $user_fame[$u]['l'],
                        'g' => $user_fame[$u]['g']
                    ];
                } else {
                    $miss[] = $u;
                }
            }
        }
        $miss = array_unique($miss);
        cache($this->quest->name . 'getAns.miss_real', implode(',', $miss));
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
        cache($this->quest->name . 'getAns.miss', $miss);
        return $data;
    }

    public function getAvgGroupByLabel($data)
    {
        $group_data = [];
        foreach ($data as $item) {
            if (in_array($item['l'], ['换届选举监委会'])) {
                continue;
            }
            if (!isset($group_data[$item['l']])) {
                $group_data[$item['l']] = [];
            }
            $group_data[$item['l']][] = $item;
        }
        $all_label_data = [];
        foreach ($group_data as $key => $v) {
            $label_data = $this->getAvg($v);
            $all_label_data[] = [$key, $label_data];
        }
        assert(count($all_label_data) === count($group_data));

        $ret = [];
        $ans = [];
        $log_str = "getAvgGroupByLabel: ";
        for ($i = 0; $i < count($this->quest->test); $i++) {
            $test = $this->quest->test[$i];
            if (!isset($test['a'])) {
                continue;
            }
            $tmp = ['q' => $test['q']];
            foreach ($this->quest->obj as $o) {
                $tmp[$o] = 0;
                $used_label_num = 0;
                $log_str .= "$o -> ";
                foreach ($all_label_data as $item) {
                    $current_q = $item[1][count($ret)];
                    if (!isset($current_q[$o])) {
                        continue;
                    }
                    $tmp[$o] += floatval($current_q[$o]);
                    $used_label_num++;
                    $log_str .= "" . $item[0] . " " . floatval($current_q[$o]) . ";";
                }
                $log_str .= " <- $used_label_num;";
                if ($used_label_num === 0) {
                    $tmp[$o] = 0;
                } else {
                    $tmp[$o] /= $used_label_num;
                }
                if (!isset($ans[$o])) {
                    $ans[$o] = 0;
                }
                $ans[$o] += $tmp[$o];
                $tmp[$o] = number_format($tmp[$o], 2, '.', '');
            }
            $ret[] = $tmp;
        }
        $tmp = ['q' => "总分（{$this->quest->max_score}分）"];
        foreach ($this->quest->obj as $o) {
            if (isset($ans[$o])) {
                $tmp[$o] = number_format($ans[$o], 2, '.', '');
            }
        }
        $ret[] = $tmp;
//        if (session('name') === HBConfig::CODER) {
//            trace($log_str, MysqlLog::INFO);
//        }
        return $ret;
    }

    public function getAvg($data)
    {
        $cnt = [];
        foreach ($this->quest->obj as $o) {
            $cnt[$o] = 0;
        }
        $ans = [];
        foreach ($data as $item) {
            $weight = 0;
            if (in_array($item['g'], $this->quest->fame_power2)) {
                $weight = 2.0;
            } elseif (in_array($item['g'], $this->quest->fame_power1)) {
                $weight = 1.0;
            } elseif (in_array($item['g'], $this->quest->fame_power_half)) {
                $weight = 0.5;
            }
            $cnt[$item['o']] += $weight;
            for ($i = 0; $i < count($this->quest->test); $i++) {
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
        for ($i = 0; $i < count($this->quest->test); $i++) {
            $test = $this->quest->test[$i];
            if (!isset($test['a'])) {
                continue;
            }
            $tmp = ['q' => $test['q']];
            foreach ($this->quest->obj as $o) {
                if (isset($ans[$o])) {
                    if ($cnt[$o] === 0) {
                        $ans[$o][$i] = 0;
                    } else {
                        $ans[$o][$i] /= $cnt[$o];
                    }
                    $ans[$o][$i] = number_format($ans[$o][$i], 2, '.', '');
                    $tmp[$o] = $ans[$o][$i];
                }
            }
            $ret[] = $tmp;
        }
        $tmp = ['q' => "总分（{$this->quest->max_score}分）"];
        foreach ($this->quest->obj as $o) {
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
                $tmp = $this->quest->test[$i];
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


    public static function cmp($a, $b)
    {
        if ($a['o'] !== $b['o']) {
            return $a['o'] > $b['o'] ? 1 : -1;
        }
        if ($a['q'] !== $b['q']) {
            return $a['q'] > $b['q'] ? 1 : -1;
        }
        if ($a['s'] !== $b['s']) {
            return $a['s'] > $b['s'] ? 1 : -1;
        }
        if ($a['t'] !== $b['t']) {
            return $a['t'] > $b['t'] ? 1 : -1;
        }
        return 0;
    }

    /**
     * @throws
     */
    private function progress()
    {
        $all = $this->getAll();
        $len = count($all) * count($this->quest->obj);

        $map = [
            'year' => HBConfig::YEAR,
            'catg' => $this->catg,
            'name' => ['in', $this->quest->obj],
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
        $len = count($this->quest->test);
        if (!isset($ans['sel']) || !is_array($ans['sel']) || count($ans['sel']) !== $len) {
            throw new HttpResponseException(json(['msg' => '没有答案啊？'], 400));
        }
        if (array_sum($ans['sel']) === $this->quest->max_score) {
            throw new HttpResponseException(json(['msg' => '不能给满分！'], 400));
        }
        if (!isset($ans['sel_add'])) {
            $ans['sel_add'] = [];
        }
        if (!is_array($ans['sel_add'])) {
            throw new HttpResponseException(json(['msg' => '文字类型不匹配！'], 400));
        }
        foreach (range(0, $len - 1) as $i) {
            $tmp = $this->quest->test[$i];
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
        $ret = "\n\n提取投票......{$this->quest->name}";
        if (!in_array($uname, $this->getUser())) {
            return "$ret\n身份验证......失败\n";
        }
        $ret = "$ret\n身份验证......成功";
        if (self::IsExpired()) {
            return "$ret\n投票......已关闭\n\n";
        }

        $rest_time = self::GetRestTime();
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

        foreach ($this->quest->obj as $item) {
            $nonce = WxHanbj::setJump('wxtest', "$item-{$this->catg}", $uname, 60 * 60);
            if (!in_array($item, $ans_list)) {
                $unfinish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">$item</a>\n";
            } else {
                $finish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">已完成-$item</a>\n";
            }
        }
        return $ret . $sep . $unfinish . $sep . $finish . $sep;
    }

    /**
     * @param $uname
     * @param $obj
     * @param $ans
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws PDOException
     * @throws \think\Exception
     */
    public function addAns($uname, $obj, $ans)
    {
        $user_id = MemberOper::getIdUnameMap([$uname]);
        $id_key = $this->calc_int($obj, intval($user_id[$uname]));
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
            if (!TodoOper::TestTypeKeyValid(TodoOper::VOTE_ORG, $id_key)) {
                TodoOper::handleTodo(TodoOper::VOTE_ORG, $id_key, TodoOper::DONE);
            }
        } catch (Exception $e) {
            $e = $e->getMessage();
            preg_match('/Duplicate entry \'(.*)-(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                if (!TodoOper::TestTypeKeyValid(TodoOper::VOTE_ORG, $id_key)) {
                    TodoOper::handleTodo(TodoOper::VOTE_ORG, $id_key, TodoOper::DONE);
                }
                return json(['msg' => 'OK']);
            }
            trace("Test UP $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
        return json(['msg' => 'OK']);
    }

    /**
     * @param string $target
     * @param int $id_ret
     * @return int
     */
    private function calc_int($target, $id_ret)
    {
        // who am i? no known
        $ret = $id_ret;
        // who we vote for? should be less than 100
        $id_target = intval(array_search($target, $this->quest->obj, true));
        $ret *= 100;
        $ret += $id_target;
        // what program we vote for? should be less than 100
        $id_catg = intval($this->catg);
        $ret *= 100;
        $ret += $id_catg;
        // which year? should be less than 100
        $ret *= 100;
        $ret += HBConfig::YEAR;
        return $ret;
    }

    /**
     * @throws
     */
    public function try_add_todo()
    {
        if (self::IsExpired()) {
            return;
        }
        $cache_key = $this->quest->name . "try_add_todo";
        if (cache("?$cache_key")) {
            return;
        }
        cache($cache_key, $cache_key, 3600);

        $todo_uname = "" . cache($this->quest->name . 'getAns.miss_real');
        $todo_uname = explode(",", $todo_uname);
        if (count($todo_uname) == 0) {
            return;
        }
        $id_user_map = MemberOper::getIdUnameMap($todo_uname);
        foreach ($todo_uname as $uname) {
            foreach ($this->quest->obj as $target) {
                $key = $this->calc_int($target, intval($id_user_map[$uname]));
                if (!TodoOper::TestTypeKeyValid(TodoOper::VOTE_ORG, $key)) {
                    continue;
                }
                TodoOper::RecvTodoFromOtherOper(
                    TodoOper::VOTE_ORG,
                    $key,
                    json_encode([
                        "name" => $this->quest->name,
                        "target" => $target
                    ]),
                    $uname);
            }
        }
    }

    /**
     * @throws \think\Exception
     * @throws PDOException
     */
    public static function cancel_all_todo()
    {
        if (!self::IsExpired()) {
            return;
        }
        $ret = Db::table('todo')
            ->where([
                'type' => TodoOper::VOTE_ORG,
                'status' => TodoOper::UNDO
            ])
            ->data(['status' => TodoOper::FAIL_FOREVER])
            ->update();
        if ($ret !== 0) {
            trace("Cancel VOTE_ORG todo $ret", MysqlLog::INFO);
        }
    }
}
