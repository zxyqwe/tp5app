<?php

namespace hanbj\vote;

use hanbj\FameOper;
use think\Db;
use think\exception\HttpResponseException;
use hanbj\weixin\WxHanbj;

class WxOrg
{
    const year = 12;
    const name = '2017会长层测评';
    const test = [
        [
            'q' => '你对A会长分管部门（无具体分管的，则评价各个部门）工作情况评价（40分）'
        ],
        [
            'q' => '任务完成及实际效果（10分）',
            'a' => [
                '超额完成工作任务，效果完美，意义影响大（10分）',
                '完成所有工作任务，效果有积极作用（8-9分）',
                '基本完成工作任务，工作效果平平（6-7分）',
                '有明显未完成的任务，且没有替代性工作；或工作效果差，甚至有负面影响（5分及以下）'
            ]
        ],
        [
            'q' => '团队管理与协作（10分）',
            'a' => [
                '内部分工清晰科学、配合顺畅，凝聚力很强10分）',
                '有一定的分工协作，具有团队意识（8-9分）',
                '成员勤怠程度参差不齐，勉强算是团队（6-7分）',
                '几乎无分工配合，整体散漫懈怠（5分及以下）'
            ]
        ],
        [
            'q' => '人才培养与个人纪律（10分）',
            'a' => [
                '骨干很好地带动教导干事，成员很好地履行参会、活动等义务（10分）',
                '骨干做给干事看，有心人能有所提高，成员基本能守纪律（8-9分）',
                '骨干爱单干，干事难以提高，有不守纪律情况发生（6-7分）',
                '完全不管干事培养问题，成员违纪情况时有发生。（5分及以下）'
            ]
        ],
        [
            'q' => '工作创新和团队进取心（10分）',
            'a' => [
                '团队思维活跃，工作上各种推陈出新（10分）',
                '不满足于按部就班，能作出一些新业绩（8-9分）',
                '基本按照固有套路工作，偶有创新改进（6-7分）',
                '无进取心和创新意识，应付工作（5分及以下）'
            ]
        ],
        [
            'q' => '你对A会长个人工作状态的评价（60分）'
        ], [
            'q' => '履职尽责（本题与其他题目不同，按照分支问题给分即可，共20分）',
            'a' => [
                '带头积极参加全年会议与活动（6分）',
                '主动协调、指导和配合会务或活动组织（4分）',
                '遵守会议纪律和活动纪律（5分）',
                '及时批复签报，参与或作出决策（5分）'
            ],
            's' => 20
        ],
        [
            'q' => '工作态度（10分）',
            'a' => [
                '任劳任怨，用于担当，竭尽所能完成工作。10分',
                '有责任心，能较好完成分内工作。8-9分',
                '需要提醒督促勉强完成工作。6-7分',
                '敷衍了事,态度傲慢，无责任心，粗心、拖拉。5分及以下'
            ]
        ],
        [
            'q' => '领导能力（10分）',
            'a' => [
                '善于分配工作，积极传授工作知识技能，引导下属完成任务。10分',
                '能够较好的分配工作，有效传授工作知识技能，指导下属完成工作任务。8-9分',
                '本能够合理地分配工作，具备一定指导下属工作的能力。6-7分',
                '欠缺分配工作及指导下属的工作方法，工作任务完成偶有困难。5分及以下'
            ]
        ],
        [
            'q' => '团队意识（10分）',
            'a' => [
                '工作有计划，与其他会长和分管部门配合无间，工作完成顺利。10分',
                '爱护团体，常主动帮助指导别人。8-9分',
                '肯应他人要求帮助别人。6-7分',
                '自由散漫，蛮横不妥协，难以合作。5分及以下'
            ]
        ],
        [
            'q' => '创新意识（10分）',
            'a' => [
                '创新能力和大局观强，在工作改善方面，常提出建设性意见并采纳。10分',
                '有一定创新意识和能力，有时在工作方法上有改进。8-9分',
                '创新意识一般，偶尔有改进建议，能完成任务。6-7分',
                '毫无创新，只能基本完成任务；5分及以下'
            ]
        ]
    ];

    function __construct()
    {
        $this->upper = FameOper::getUp();
        $this->lower = FameOper::getDeputy();
        $this->obj = FameOper::getTop();
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
        foreach ($user as $u) {
            foreach ($this->obj as $o) {
                $c_name = $u . $o . WxOrg::name;
                if (cache('?' . $c_name)) {
                    $data[] = [
                        'ans' => json_decode(cache($c_name), true),
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
        cache(WxOrg::name . 'getAns.miss', $miss);
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
            for ($i = 0; $i < count(self::test); $i++) {
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
        for ($i = 0; $i < count(self::test); $i++) {
            $test = self::test[$i];
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
                        'q' => self::test[$i]['q'],
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
        $acc = 0;
        foreach ($this->obj as $obj) {
            foreach ($all as $item) {
                $c_name = $item . $obj . self::name;
                if (cache('?' . $c_name)) {
                    $acc++;
                }
            }
        }
        if ($acc !== $len) {
            $res = $acc * 100.0 / $len;
            return self::name . "\n投票数量：$acc / $len\n总进度：" . round($res, 2) . "%\n";
        } else {
            return false;
        }
    }

    public static function checkAns(&$ans)
    {
        if (!is_array($ans)) {
            throw new HttpResponseException(json(['msg' => 'array'], 400));
        }
        $len = count(self::test);
        if (!isset($ans['sel']) || !is_array($ans['sel']) || count($ans['sel']) !== $len) {
            throw new HttpResponseException(json(['msg' => 'sel'], 400));
        }
        if (array_sum($ans['sel']) === 100) {
            throw new HttpResponseException(json(['msg' => '满分'], 400));
        }
        if (!isset($ans['sel_add'])) {
            $ans['sel_add'] = [];
        }
        if (!is_array($ans['sel_add'])) {
            throw new HttpResponseException(json(['msg' => 'sel_add'], 400));
        }
        foreach (range(0, $len - 1) as $i) {
            $tmp = self::test[$i];
            if (!isset($tmp['a'])) {
                continue;
            }
            $s = 10;
            if (isset($tmp['s'])) {
                $s = $tmp['s'];
            }
            $s *= 0.6;
            if ($ans['sel'][$i] < $s
                && (!isset($ans['sel_add'][$i]) || count($ans['sel_add'][$i]) < 15)
            ) {
                throw new HttpResponseException(json(['msg' => 'sel ' . $i], 400));
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
        foreach ($this->obj as $item) {
            $c_name = $uname . $item . self::name;
            $nonce = WxHanbj::setJump('wxtest', $item, $uname, 60 * 20);
            if (!cache('?' . $c_name)) {
                $unfinish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">$item</a>\n";
            } else {
                $finish .= "<a href=\"https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce\">已完成-$item</a>\n";
            }
        }
        return $ret . $unfinish . $finish;
    }
}
