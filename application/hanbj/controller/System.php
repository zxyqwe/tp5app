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
use think\exception\HttpResponseException;
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

    public function test()
    {
        $ret = [];
        foreach (WxOrg::vote_cart as $catg) {
            $org = new WxOrg($catg);
            $ans = $org->getAns();
            $ratio = count($ans) * 100.0 / count($org->getAll()) / count($org->obj);
            $ratio = number_format($ratio, 2, '.', '');
            $miss = cache($org->name . 'getAns.miss');
            $ret[] = [
                'avg' => $org->getAvg($ans),
                'cmt' => $org->getComment($ans),
                'obj' => $org->obj,
                'mis' => $miss,
                'rto' => $ratio,
                'all' => implode(', ', MemberOper::pretty_tieba(MemberOper::get_tieba($org->getAll()))),
                'name' => $org->name
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
        return view('token', ['data' => $map]);
    }
}
