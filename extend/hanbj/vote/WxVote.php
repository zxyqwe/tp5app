<?php

namespace hanbj\vote;

use DateTimeImmutable;
use Exception;
use hanbj\FameOper;
use hanbj\HBConfig;
use hanbj\MemberOper;
use hanbj\TodoOper;
use PDOStatement;
use think\Collection;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\exception\PDOException;
use util\MysqlLog;

class WxVote
{
    const HISTORY = [
        // 紫菀灯芯  采娈  鸿胪寺少卿  夜娘_魁児
        13 => ['离庚寅', '艮甲辰', '乾甲申', '乾壬申'],
        // 夜娘·魁児  荼蘼未开  颜真真  未名
        14 => ['乾壬申', '兑甲戌', '夏庚子', '商丙子'],
        // 夜娘·魁児  荼蘼未开  颜真真  未名
        15 => ['乾壬申', '兑甲戌', '夏庚子', '商丙子']
    ];

    private static function GetDeadline()
    {
        return DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2019-12-22 13:30:00");
    }

    public static function IsExpired()
    {
        $deadline = self::GetDeadline();
        $now = new DateTimeImmutable();
        return $now > $deadline;
    }

    private static function GetRestTime()
    {
        $deadline = self::GetDeadline();
        $now = new DateTimeImmutable();
        return $deadline->diff($now);
    }

    /**
     * @return array|null
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public static function initView()
    {
        $member_code = session('member_code');
        if (!is_numeric($member_code) || intval($member_code) !== MemberOper::NORMAL) {
            return null;
        }

        $map = self::getMap(HBConfig::YEAR);
        $ret = Db::table('vote')
            ->where([
                'unique_name' => session('unique_name'),
                'year' => HBConfig::YEAR
            ])
            ->field([
                'ans'
            ])
            ->find();
        if (null === $ret) {
            $ret = [];
        } else {
            $ret = explode(',', $ret['ans']);
        }
        $front = [];
        foreach ($ret as $item) {
            if (!isset($map[$item])) {
                continue;
            }
            $map[$item]['sel'] = true;
            $front[] = $map[$item];
            unset($map[$item]);
        }
        foreach (array_values($map) as $item) {
            $front[] = $item;
        }
        return $front;
    }

    private static function getMap($target_year)
    {
        $res = MemberOper::get_tieba(self::HISTORY[$target_year]);
        $map = [];
        foreach ($res as $item) {
            $map[$item['u']] = $item;
            $map[$item['u']]['s'] = $item['u'] . '~' . $item['t'];
            $map[$item['u']]['sel'] = false;
        }
        return $map;
    }

    public static function addAns($uniq, $ans)
    {
        $data = [
            'unique_name' => $uniq,
            'year' => HBConfig::YEAR,//这一届投票下一届
            'ans' => implode(',', $ans)
        ];
        try {
            $ret = Db::table('vote')
                ->where([
                    'unique_name' => $uniq,
                    'year' => HBConfig::YEAR,//这一届投票下一届
                ])
                ->data(['ans' => $data['ans']])
                ->update();
            if ($ret <= 0) {
                Db::table('vote')
                    ->insert($data);
                trace("选举add $uniq {$data['ans']}", MysqlLog::INFO);
            } else {
                trace("选举update $uniq {$data['ans']}", MysqlLog::INFO);
            }
            $ret = Db::table('member')
                ->where(['unique_name' => $uniq])
                ->field(['id'])
                ->find();
            if (null !== $ret) {
                // every year, vote once. so only my id num need
                $key = intval($ret['id']) * 1000 + HBConfig::YEAR;
                if (!TodoOper::TestTypeKeyValid(TodoOper::VOTE_TOP, $key)) {
                    TodoOper::handleTodo(TodoOper::VOTE_TOP, $key, TodoOper::DONE);
                }
            }
        } catch (Exception $e) {
            $e = $e->getMessage();
            preg_match('/Duplicate entry \'(.*)-(.*)\' for key/', $e, $token);
            if (isset($token[2])) {
                return json(['msg' => 'OK']);
            }
            trace("Test Vote $e", MysqlLog::ERROR);
            throw new HttpResponseException(json(['msg' => $e], 400));
        }
        return json(['msg' => 'OK']);
    }

    /**
     * @param int $target_year
     * @return array|false|mixed|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getResult($target_year)
    {
        $target_year = intval($target_year);
        if (!array_key_exists($target_year, self::HISTORY)) {
            $target_year = HBConfig::YEAR;
        }
        $cache_name = "WxVote::getResult$target_year";
        if (cache("?$cache_name")) {
            return json_decode(cache($cache_name), true);
        }

        $join = [
            ['member m', 'm.unique_name=v.unique_name', 'left'],
            ['fame f', 'f.unique_name=v.unique_name and f.year=' . $target_year, 'left']
        ];
        $map = [
            'm.code' => MemberOper::NORMAL,
            'v.year' => $target_year
        ];
        $ans = Db::table('vote')
            ->alias('v')
            ->join($join)
            ->where($map)
            ->field([
                'v.ans as a',
                'f.grade as g'
            ])
            ->select();
        if (self::IsExpired()) {
            $last = "0 天 0 时 0 分 0 秒";
        } else {
            $last = self::GetRestTime();
            $last = $last->format("%a 天 %H 时 %i 分 %s 秒");
        }
        $ans = [
            'zg' => self::test_ZG($ans, $target_year),
            'pw' => self::test_PW($ans, $target_year),
            'ref' => date("Y-m-d H:i:s"),
            'last' => $last,
            'year' => $target_year + 1
        ];
        cache($cache_name, json_encode($ans), 600);
        return $ans;
    }

    public static function trans_rules()
    {
        $idx = 0;
        $ret = "剩余时间：";
        if (self::IsExpired()) {
            $last = "0 天 0 时 0 分 0 秒";
        } else {
            $last = self::GetRestTime();
            $last = $last->format("%a 天 %H 时 %i 分 %s 秒");
        }
        $ret .= $last;
        while ($idx <= FameOper::max_pos) {
            $ret .= "\n" . FameOper::translate($idx) . "：票力" . self::get_weight($idx) . "；";
            $idx++;
        }
        $idx = null;
        $ret .= "\n" . FameOper::translate($idx) . "：票力" . self::get_weight($idx) . "；";
        return $ret;
    }

    public static function get_weight($grade)
    {
        if ($grade === null) {
            return 1;
        }
        $grade = intval($grade);
        if (in_array($grade, [
            FameOper::chairman,
            FameOper::vice_chairman,
            FameOper::fixed_vice_chairman,
            FameOper::manager,
            FameOper::vice_manager,
            FameOper::commissioner,
            FameOper::secretary,
            FameOper::vice_secretary,
            FameOper::fame_chair,
            FameOper::like_manager
        ])) {
            return 3;
        }

        if ($grade !== FameOper::leave) {
            return 2;
        }
        return 1;
    }

    private static function test_ZG($ans, $target_year)
    {
        $total = 0;
        $candidate = [];
        $map = self::getMap($target_year);
        foreach (self::HISTORY[$target_year] as $item) {
            $candidate[$map[$item]['s']] = 0;
        }
        foreach ($ans as $item) {
            $tmp = explode(',', $item['a']);
            $weight = self::get_weight($item['g']);
            $total += $weight;
            foreach ($tmp as $idx) {
                $candidate[$map[$idx]['s']] += $weight;
            }
        }
        return ['tot' => $total, 'detail' => $candidate];
    }

    private static function test_PW($ans, $target_year)
    {
        $total = 0;
        $candidate = [];
        $map = self::getMap($target_year);
        foreach (self::HISTORY[$target_year] as $item) {
            $candidate[$map[$item]['s']] = 0;
        }
        foreach ($ans as $item) {
            if ($item['g'] === null) {
                continue;
            }
            $weight = count(self::HISTORY[$target_year]);
            $total += $weight;
            $tmp = explode(',', $item['a']);
            foreach ($tmp as $idx) {
                $candidate[$map[$idx]['s']] += $weight;
                $weight--;
            }
        }
        return ['tot' => $total, 'detail' => $candidate];
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function try_add_todo()
    {
        if (self::IsExpired()) {
            return;
        }
        $cache_key = "WxVote.phptry_add_todo";
        if (cache("?$cache_key")) {
            return;
        }
        cache($cache_key, $cache_key, 3600);

        $real_ret = Db::table("member")
            ->where([
                'code' => ['in', MemberOper::getAllReal()],
                'openid' => ['exp', Db::raw('is not null')]
            ])
            ->field(['unique_name', 'id'])
            ->select();
        $todo_uname = [];
        $uname_id_map = [];
        foreach ($real_ret as $u) {
            $todo_uname[] = $u['unique_name'];
            $uname_id_map[$u['unique_name']] = $u['id'];
        }
        $vote_ret = Db::table("vote")
            ->where(['year' => HBConfig::YEAR])
            ->field(['unique_name'])
            ->select();
        $done_uname = [];
        foreach ($vote_ret as $u) {
            $done_uname[] = $u['unique_name'];
        }
        $todo_uname = array_diff($todo_uname, $done_uname);
        if (count($todo_uname) == 0) {
            return;
        }

        $vote_name = "第" . (HBConfig::YEAR + 1) . "届会长层换届选举";
        foreach ($todo_uname as $uname) {
            // every year, vote once. so only my id num need
            $key = intval($uname_id_map[$uname]) * 1000 + HBConfig::YEAR;
            if (!TodoOper::TestTypeKeyValid(TodoOper::VOTE_TOP, $key)) {
                continue;
            }
            TodoOper::RecvTodoFromOtherOper(
                TodoOper::VOTE_TOP,
                $key,
                json_encode([
                    "name" => $vote_name,
                ]),
                $uname);
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
                'type' => TodoOper::VOTE_TOP,
                'status' => TodoOper::UNDO
            ])
            ->data(['status' => TodoOper::FAIL_FOREVER])
            ->update();
        if ($ret !== 0) {
            trace("Cancel VOTE_TOP todo $ret", MysqlLog::INFO);
        }
    }
}
