<?php

namespace util\stat;

use DateInterval;
use DateTimeImmutable;
use hanbj\HBConfig;
use hanbj\MemberOper;
use hanbj\OrderOper;
use hanbj\TodoOper;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use util\MysqlLog;
use util\StatOper;

class HanbjWeekStat extends BaseStat
{
    function __construct()
    {
        $this->today = new DateTimeImmutable();
        $this->today = $this->today->setTime(0, 0, 0);
        $this->first_day = DateTimeImmutable::createFromFormat(StatOper::TIME_FORMAT, "2019-01-28");
        $this->first_day = $this->first_day->setTime(0, 0, 0);
        $this->time_interval = new DateInterval("P7D");
    }

    /**
     * @return array|bool|false
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function generateOneDay()
    {
        $fetch_date = self::fetch_date(StatOper::HANBJ_WEEK_REPORT);
        if ($fetch_date === false) {
            return false;
        }
        trace("HanbjWeekStat::generateOneDay " . $fetch_date->format(StatOper::TIME_FORMAT), MysqlLog::INFO);
        $start_date = $fetch_date->sub($this->time_interval);
        $time_range = [$start_date->format("Y-m-d H:i:s"), $fetch_date->format("Y-m-d H:i:s")];

        $ret = $this->generateData($time_range);
        $ret = json_encode($ret);
        return [
            $fetch_date->format(StatOper::TIME_FORMAT),
            $ret,
            "周报 " . $time_range[0] . " ~ " . $time_range[1] . " ，长度 " . strlen($ret)
        ];
    }

    /**
     * @param $time_range
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function generateData($time_range)
    {
        $data = [
            "tr" => $time_range
        ];
        // 活动：登记了几个人，有几个活动，有多少积分，多少志愿者
        $act_ret = Db::table("activity")
            ->where([
                'act_time' => ['between', $time_range]
            ])
            ->field([
                "count(distinct name) as an",
                "count(distinct unique_name) as un",
                "sum(bonus) as ab",
                "sum(type) as vb"
            ])
            ->find();
        $data['act'] = $act_ret;
        // 微信会员卡：当前发送量，有几个未激活的
        $wx_card = Db::table("card")
            ->field([
                "count(distinct openid) as c",
                "sum(status) as s"
            ])
            ->find();
        $data['wxc'] = $wx_card;
        // 本届吧务组：有多少个部门，总共多少人
        $fame_ret = Db::table("fame")
            ->where([
                'year' => HBConfig::YEAR
            ])
            ->field([
                "count(distinct unique_name) as un",
                "count(distinct label) as nl"
            ])
            ->find();
        $fame_ret2 = Db::table("fame")
            ->where([
                'year' => HBConfig::YEAR
            ])
            ->field([
                "grade as g",
                "count(1) as c"
            ])
            ->group("grade")
            ->select();
        $data["fame"] = [
            "f1" => $fame_ret,
            "f2" => $fame_ret2
        ];
        // 日志：微信事件、与小程序交互次数
        $rpc = Db::table("logs")
            ->where([
                "time" => ['between', $time_range],
                "type" => MysqlLog::RPC
            ])
            ->field([
                "count(1) as c"
            ])
            ->find();
        $wx_log = Db::table("logs")
            ->where([
                "time" => ['between', $time_range],
                "msg" => ["like", "WxEvent%"]
            ])
            ->field("msg")
            ->select();
        $wx_log_kv = [];
        foreach ($wx_log as $item) {
            $item = explode(" ", $item['msg']);
            $item = end($item);
            if (array_key_exists($item, $wx_log_kv)) {
                $wx_log_kv[$item] += 1;
            } else {
                $wx_log_kv[$item] = 1;
            }
        }
        $data['log'] = [
            "rpc" => $rpc,
            "wx" => $wx_log_kv
        ];
        // 会员：汇总当前会员状态，可选号码数量，新加入会员数量
        $group = Db::table("member")
            ->where([
                "code" => ["neq", MemberOper::TEMPUSE]
            ])
            ->group("code")
            ->field([
                "count(1) as c",
                "code as s"
            ])
            ->select();
        $unused_ret = Db::table('member')
            ->where([
                "code" => MemberOper::UNUSED,
                "id" => ['>', HBConfig::FIRST_UNAME_ID]
            ])
            ->field('count(1) as c')
            ->find();
        $new_group = Db::table("member")
            ->where([
                "start_time" => ['between', $time_range],
                "code" => ["neq", MemberOper::TEMPUSE]
            ])
            ->group("code")
            ->field([
                "count(1) as c",
                "code as s"
            ])
            ->select();
        $data['m'] = [
            "g" => $group,
            "un" => $unused_ret,
            "ng" => $new_group
        ];
        // 会费：新发生的事件，对应的积分
        $nfee = Db::table("nfee")
            ->where([
                "fee_time" => ['between', $time_range]
            ])
            ->group("code")
            ->field([
                "count(distinct unique_name) as un",
                "code as s",
                "count(1) as c",
                "sum(bonus) as b"
            ])
            ->select();
        $data['fee'] = $nfee;
        // 收入：订单情况
        $order = Db::table("order")
            ->where([
                "type" => OrderOper::FEE,
                "time" => ['between', $time_range]
            ])
            ->group("label")
            ->field([
                "count(1) as c",
                "label as l"
            ])
            ->select();
        $data['o'] = $order;
        // 支出：新增情况，实名认证
        $real = Db::table("payout")
            ->where([
                "gene_time" => ['between', $time_range],
                "actname" => "实名认证"
            ])
            ->group("status")
            ->field([
                "status as s",
                "count(1) as c"
            ])
            ->select();
        $pay = Db::table("payout")
            ->where([
                "gene_time" => ['between', $time_range],
                "actname" => ["neq", "实名认证"]
            ])
            ->group("status")
            ->field([
                "status as s",
                "count(1) as c",
                "sum(fee) as f"
            ])
            ->select();
        $data['pay'] = [
            "r" => $real,
            "p" => $pay
        ];
        return $data;
    }

    /**
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function OutputAll()
    {
        $fetch_date = $this->today;
        $start_date = $fetch_date->sub($this->time_interval);
        $time_range = [$start_date->format("Y-m-d H:i:s"), $fetch_date->format("Y-m-d H:i:s")];
        return $this->generateData($time_range);
    }

    /**
     * @param $select_ret
     * @param $all_catg
     * @return array
     */
    protected function build_kv($select_ret, $all_catg)
    {
        return [];
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function addLastTodo()
    {
        $ret = StatOper::getQuery(StatOper::HANBJ_WEEK_REPORT)
            ->order('time desc')
            ->field('time as t')
            ->find();
        $current_new_day = DateTimeImmutable::createFromFormat(StatOper::TIME_FORMAT, $ret['t']);
        $current_new_day = $current_new_day->setTime(0, 0, 0);
        $key = $current_new_day->getTimestamp();
        $notified_users = [HBConfig::CODER];
        $user_map = MemberOper::getIdUnameMap($notified_users);
        foreach ($notified_users as $user) {
            $user_id = intval($user_map[$user]);
            $user_key = $key + $user_id;
            if (!TodoOper::TestTypeKeyValid(TodoOper::WEEK_REPORT, $user_key)) {
                continue;
            }
            trace("Add WeekReport $user : $key + $user_id = $user_key", Mysqllog::LOG);
            TodoOper::RecvTodoFromOtherOper(TodoOper::WEEK_REPORT, $user_key, $ret['t'], $user);
        }
    }
}