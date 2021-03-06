<?php

namespace hanbj\vote\quest;

use hanbj\FameOper;
use hanbj\MemberOper;
use hanbj\vote\WxQuest;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class WxQTop extends WxQuest
{
    /**
     * WxQTop constructor.
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
            FameOper::commissioner,
            FameOper::vice_secretary
        ];
        $this->fame_power_half = [];
        $this->obj = [];
        $ret = MemberOper::get_tieba(FameOper::get([
            FameOper::chairman,
            FameOper::vice_chairman
        ]));
        foreach ($ret as $item) {
            $this->obj[] = "{$item['u']}~{$item['t']}";
        }
        $this->max_score = 80;
        $this->name = date('Y') . '年度会长层测评';
        $this->test = [
            [
                'q' => '你对该会长个人工作状态的评价'
            ],
            [
                'q' => '1.履职尽责（本题与其他题目不同，按照分支问题给分即可，共20分）',
                'a' => [
                    '带头积极参加全年会议与活动（6分）',
                    '主动协调、指导和配合会务或活动组织（4分）',
                    '遵守会议纪律和活动纪律（5分）',
                    '及时批复签报，参与或作出决策（5分）'
                ],
                's' => 20
            ],
            [
                'q' => '2.团队意识与人才培养（本题与其他题目不同，按照分支问题给分即可，共20分）',
                'a' => [
                    '工作有计划，能够与其他会长及团队协作配合，工作完成顺利（8分）',
                    '爱护团体，注重团体意识，常主动帮助指导别人（6分）',
                    '能很好地带动骨干，发掘骨干特长技能，积极培养中坚力量（6分）'
                ],
                's' => 20
            ],
            [
                'q' => '3.工作态度（10分）',
                'a' => [
                    '任劳任怨，用于担当，竭尽所能完成工作（10分）',
                    '有责任心，能较好完成分内工作（8-9分）',
                    '需要提醒督促勉强完成工作（6-7分）',
                    '敷衍了事,态度傲慢，无责任心，粗心、拖拉（5分及以下）'
                ]
            ],
            [
                'q' => '4.领导能力（10分）',
                'a' => [
                    '善于分配工作，积极传授工作知识技能，引导下属完成任务（10分）',
                    '能够较好的分配工作，有效传授工作知识技能，指导下属完成工作任务（8-9分）',
                    '本能够合理地分配工作，具备一定指导下属工作的能力（6-7分）',
                    '欠缺分配工作及指导下属的工作方法，工作任务完成偶有困难（5分及以下）'
                ]
            ],
            [
                'q' => '5.创新意识（10分）',
                'a' => [
                    '创新能力和大局观强，在工作改善方面，常提出建设性意见并采纳（10分）',
                    '有一定创新意识和能力，有时在工作方法上有改进（8-9分）',
                    '创新意识一般，偶尔有改进建议，能完成任务（6-7分）',
                    '毫无创新，只能基本完成任务（5分及以下）'
                ]
            ],
            [
                'q' => '6.改善能力（10分）',
                'a' => [
                    '对工作中出现的新问题能够及时发现处理，并很好地批评总结，作出改善避免再次发生（10分）',
                    '对工作中出现的新问题能及时发现处理，做好总结和改善方案。（8-9分）',
                    '对工作中出现的新问题做到基本解决（6-7分）',
                    '完全无视新问题（5分及以下）'
                ]
            ]
        ];
    }
}
