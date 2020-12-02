<?php

namespace hanbj\vote\quest;

use hanbj\FameOper;
use hanbj\vote\WxQuest;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class WxQDep extends WxQuest
{
    /**
     * WxQDep constructor.
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    function __construct()
    {
        $this->fame_power2 = [
            FameOper::chairman,
            FameOper::vice_chairman,
            FameOper::fixed_vice_chairman,
            FameOper::secretary,
            FameOper::manager
        ];
        $this->fame_power1 = [
            FameOper::vice_manager,
            FameOper::fame_chair,
            FameOper::like_manager,
            FameOper::vice_secretary
        ];
        $this->fame_power_half = [
            FameOper::member,
            FameOper::assistant,
            FameOper::commissioner
        ];
        $ret = FameOper::getCurrentLabel();
        $this->obj = ['含章', '迎宾使'];
        foreach ($ret as $item) {
            if (in_array($item['label'], ['中枢', '秘书处', '换届选举监委会']))
                continue;
            $this->obj[] = $item['label'];
        }
        $this->max_score = 80;
        $this->name = date('Y') . '年度部门考核';
        $this->test = [
            [
                'q' => '您对该部门工作状态的评价'
            ],
            [
                'q' => '1.工作实际收效（20分）',
                'a' => [
                    '工作效果完美，意义影响大（20分）',
                    '工作效果有积极作用（15-19分）',
                    '工作效果平平，仅算完成任务（10-15分）',
                    '工作效果差，甚至有负面影响（10分及以下）'
                ],
                's' => 20
            ],
            [
                'q' => '2.团队管理与协作（20分）',
                'a' => [
                    '内部分工清晰科学、配合顺畅，凝聚力很强（20分）',
                    '有一定的分工协作，具有团队意识（15-19分）',
                    '成员勤怠程度参差不齐，勉强算是团队（10-15分）',
                    '几乎无分工配合，整体散漫懈怠（10分及以下）'
                ],
                's' => 20
            ],
            [
                'q' => '3.人才培养与纪律（20分）',
                'a' => [
                    '骨干很好地带动教导干事，成员很好地履行参会、活动等义务（20分）',
                    '骨干做给干事看，有心人能有所提高，成员基本能守纪律（15-19分）',
                    '骨干爱单干，干事难以提高，有不守纪律情况发生（10-15分）',
                    '完全不管干事培养问题，成员违纪情况时有发生。（10分及以下）'
                ],
                's' => 20
            ],
            [
                'q' => '4.工作创新和团队进取心（20分）',
                'a' => [
                    '团队思维活跃，工作上各种推陈出新（20分）',
                    '不满足于按部就班，能作出一些新业绩（15-19分）',
                    '基本按照固有套路工作，偶有创新改进（10-15分）',
                    '无进取心和创新意识，应付工作（10分及以下）'
                ],
                's' => 20
            ]
        ];
    }
}