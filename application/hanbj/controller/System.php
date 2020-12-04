<?php

namespace app\hanbj\controller;

use hanbj\BonusOper;
use hanbj\HBConfig;
use hanbj\MemberOper;
use hanbj\UserOper;
use hanbj\weixin\WxHanbj;
use think\Controller;
use think\Db;
use hanbj\vote\WxOrg;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\response\Json;
use think\response\View;
use util\StatOper;

class System extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/system_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    /**
     * @return View
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function test()
    {
        $ret = [];
        foreach (WxOrg::vote_cart as $catg) {
            $org = new WxOrg($catg);
            $ans = $org->getAns();
            $ratio = count($ans) * 100.0 / count($org->getAll()) / count($org->quest->obj);
            $ratio = number_format($ratio, 2, '.', '');
            $miss = cache($org->quest->name . 'getAns.miss');
            if ($catg == 2) {
                $avg_ans = $org->getAvgGroupByLabel($ans);
            } else {
                $avg_ans = $org->getAvg($ans);
            }
            $cmt = $org->getComment($ans);
            usort($cmt, [WxOrg::class, 'cmp']);
            $ret[] = [
                'avg' => $avg_ans,
                'cmt' => $cmt,
                'obj' => $org->quest->obj,
                'mis' => $miss,
                'rto' => $ratio,
                'all' => implode(', ', MemberOper::pretty_tieba(MemberOper::get_tieba($org->getAll()))),
                'name' => $org->quest->name,
                'rules' => $org->trans_rules()
            ];
        }
        return view('test', ['data' => json_encode($ret)]);
    }

    /**
     * @throws
     */
    public function json_order()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $res = Db::table('order')
            ->alias('f')
            ->join($join)
            ->order('f.id', 'desc')
            ->limit($offset, $size)
            ->field([
                'f.id',
                'm.unique_name as u',
                'm.tieba_id as e',
                'f.outid as o',
                'f.trans as t',
                'f.fee as f',
                'f.type as y',
                'f.value as v',
                'f.label as l',
                'f.time as i',
                'm.code'
            ])
            ->select();
        $data['rows'] = $res;
        $total = Db::table('order')
            ->alias('f')
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function hanbjorderdata()
    {
        switch ($this->request->method()) {
            case 'GET':
                return view('hanbjorderdata');
            case 'POST':
                return StatOper::OutputAll(StatOper::HANBJ_ORDER_NUM);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    /**
     * @return View
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function token()
    {
        $length = 10;
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $map['Access Key'] = substr($access, 0, $length);
        $map['Js Api'] = substr(WxHanbj::jsapi(), 0, $length);
        $map['Ticket Api'] = substr(WxHanbj::ticketapi(), 0, $length);
        $map['会费增加积分'] = BonusOper::getFeeBonus();
        $map['志愿者增加积分'] = BonusOper::getVolBonus();
        $map['活动增加积分'] = BonusOper::getActBonus();
        $map['活动预置名称'] = BonusOper::getActName();
        $res = MemberOper::get_tieba(BonusOper::getWorkers());
        $map['当前微信工作人员'] = implode('，', MemberOper::pretty_tieba($res));
        $res = MemberOper::get_tieba(UserOper::reg());
        $map['内网登录权限'] = implode('，', MemberOper::pretty_tieba($res));
        $map['内网超级权限'] = implode('，', UserOper::pretty_toplist());
        $map['当前吧务组'] = '第' . HBConfig::YEAR . '届';
        $res = MemberOper::get_open_stock();
        if (null !== $res) {
            $map['当前可选编号数量'] = $res['c'];
        }
        return view('token', ['data' => $map]);
    }

    /**
     * @return Json
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function json_payout()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $pass = input('post.pass/b', false, FILTER_VALIDATE_BOOLEAN);
        $join = [
            ['member m', 'm.openid=f.openid', 'left']
        ];
        $map = [];
        if ($pass) {
            $map['actname'] = ['neq', '实名认证'];
            $map['orgname'] = ['neq', '个人活动'];
        }
        $res = Db::table('payout')
            ->alias('f')
            ->where($map)
            ->join($join)
            ->order('f.id', 'desc')
            ->limit($offset, $size)
            ->field([
                'f.id',
                'm.unique_name as u',
                'm.tieba_id as e',
                'f.tradeid as o',
                'f.realname as t',
                'f.fee as f',
                'f.desc as y',
                'f.gene_time as v',
                'f.payment_no as l',
                'f.payment_time as i',
                'f.status as s',
                'f.nickname as n',
                'f.orgname as r',
                'f.actname as a'
            ])
            ->select();
        $data['rows'] = $res;
        $total = Db::table('payout')
            ->alias('f')
            ->where($map)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_week()
    {
        $sel_date = input("post.date");
        $sel_date = strval($sel_date);
        $ret = Db::table("stat")
            ->where([
                "type" => StatOper::HANBJ_WEEK_REPORT,
                "time" => ["eq", $sel_date]
            ])
            ->value("content");
        if (null === $ret) {
            return json(["msg" => "$sel_date 不存在"], 400);
        }
        return json(['data' => json_decode($ret, true)]);
    }
}
