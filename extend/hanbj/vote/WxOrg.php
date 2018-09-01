<?php

namespace hanbj\vote;

use hanbj\vote\quest\WxQTop;
use think\Db;
use think\exception\HttpResponseException;
use hanbj\weixin\WxHanbj;

class WxOrg
{
    const year = 13;

    function __construct($catg)
    {
        switch ($catg) {
            default:
                $quest = new WxQTop();
        }
        $this->catg = $catg;
        $this->upper = $quest->upper;
        $this->lower = $quest->lower;
        $this->obj = $quest->obj;
        $this->name = $quest->name;
        $this->test = $quest->test;
    }

    public function getAll()
    {
        return array_merge($this->upper, $this->lower);
    }

    public function getUser()
    {
        return array_merge($this->getAll(), ['坎丙午']);
    }

    public function getAns()
    {
        $user = $this->getAll();
        $data = [];
        $miss = [];

        $ans_list = [];
        $ans = Db::table('score')
            ->where([
                'year' => WxOrg::year
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
        if (count($miss) > count($user) / 2) {
            $miss = [];
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
        $tmp = ['q' => '总分（100分）'];
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
                if (!empty($cmt[$i])) {
                    $ret[] = [
                        'q' => $this->test[$i]['q'],
                        'o' => $item['o'],
                        't' => $cmt[$i],
                        's' => $sel[$i]
                    ];
                }
            }
        }
        return $ret;
    }

    private function progress()
    {
        $all = $this->getAll();
        $len = count($all) * count($this->obj);

        $acc = Db::table('score')
            ->where([
                'year' => WxOrg::year,
                'unique_name' => ['neq', '坎丙午']
            ])
            ->count('id');

        if ($acc !== $len) {
            $res = $acc * 100.0 / $len;
            return $this->name . "\n投票数量：$acc / $len\n总进度：" . round($res, 2) . "%\n";
        } else {
            return false;
        }
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
        if (array_sum($ans['sel']) === 100) {
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
            if (($ans['sel'][$i] < $s * 0.6 || $ans['sel'][$i] === $s)
                && (!isset($ans['sel_add'][$i]) || count($ans['sel_add'][$i]) < 15)
            ) {
                throw new HttpResponseException(json(['msg' => "第 $i 题没有文字！"], 400));
            }
        }
    }

    private function all_done()
    {
        return '已完成';
    }

    public function listobj($from)
    {
        $map['openid'] = $from;
        $res = Db::table('member')
            ->alias('m')
            ->where($map)
            ->field('unique_name')
            ->find();
        $uname = $res['unique_name'];
        if (!in_array($uname, $this->getUser())) {
            return "检查口令...成功\n身份验证...失败\n\n文字信息：投票";
        }
        $prog = $this->progress();
        if (false === $prog) {
            return $this->all_done();
        }
        $ret = "检查口令...成功\n身份验证...成功\n提取投票...成功\n\n有以下投票，二十分钟有效，过时重新取号\n" . $prog;
        $finish = "-----\n";
        $unfinish = "-----\n";

        $ans_list = [];
        $ans = Db::table('score')
            ->where([
                'year' => WxOrg::year,
                'unique_name' => $uname
            ])
            ->field('name')
            ->select();
        foreach ($ans as $item) {
            $ans_list[] = $item['name'];
        }

        foreach ($this->obj as $item) {
            $nonce = WxHanbj::setJump('wxtest', $item, $uname, 60 * 20);
            if (!in_array($item, $ans_list)) {
                $unfinish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">$item</a>\n";
            } else {
                $finish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">已完成-$item</a>\n";
            }
        }
        return $ret . $unfinish . $finish;
    }
}
