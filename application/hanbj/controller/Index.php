<?php

namespace app\hanbj\controller;


use app\hanbj\BonusOper;
use app\hanbj\LogUtil;
use app\hanbj\MemberOper;
use app\hanbj\UserOper;
use app\hanbj\WxHanbj;
use app\hanbj\WxOrg;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;

class Index extends Controller
{
    protected $beforeActionList = [
        'valid_id' => ['except' => 'index,old,bulletin,fame,bonus']
    ];

    protected function valid_id()
    {
        if (UserOper::VERSION !== session('login')) {
            $res = redirect('/hanbj/index/bulletin');
            throw new HttpResponseException($res);
        }
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/index_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, '页面不存在', [$action]);
    }

    public function index()
    {
        if (UserOper::VERSION === session('login')) {
            return redirect('/hanbj/index/home');
        }
        return view('login');
    }

    public function old()//需要这个，不然route就会屏蔽入口
    {
        return redirect('/hanbj/index/bulletin');
    }

    public function logout()
    {
        session(null);
        return redirect('/hanbj/index/home');
    }

    public function volunteer()
    {
        $data['name'] = BonusOper::ACT_NAME;
        $data['act'] = BonusOper::ACT;
        $data['vol'] = BonusOper::VOLUNTEER;
        return view('volunteer', ['data' => $data]);
    }

    public function runlog($data = -1)
    {
        MemberOper::daily();
        if (session('name') !== '坎丙午') {
            return redirect('/hanbj/index/home');
        }
        if (-1 < $data) {
            $par = input('post.par');
            $chi = input('post.chi');
            $data = intval($data);
            if (!is_numeric($par) || !is_numeric($chi)) {
                return json(['msg' => $par . '-' . $chi], 400);
            }
            $dir = LOG_PATH . DIRECTORY_SEPARATOR . $par . DIRECTORY_SEPARATOR . $chi . '.log';
            if (!is_file($dir)) {
                return json(['msg' => $dir], 400);
            }
            $f = file_get_contents($dir);
            return json([
                'text' => substr($f, $data),
                'len' => strlen($f)
            ]);
        }
        $data = LogUtil::list_dir(LOG_PATH, '日志');
        return view('runlog', ['data' => json_encode($data['nodes'])]);
    }

    public function token()
    {
        $length = 10;
        $access = WX_access(config('hanbj_api'), config('hanbj_secret'), 'HANBJ_ACCESS');
        $map['Access Key'] = substr($access, 0, $length);
        $map['Js Api'] = substr(WxHanbj::jsapi($access), 0, $length);
        $map['Ticket Api'] = substr(WxHanbj::ticketapi($access), 0, $length);
        $map['会费增加积分'] = BonusOper::FEE;
        $map['志愿者增加积分'] = BonusOper::VOLUNTEER;
        $map['活动增加积分'] = BonusOper::ACT;
        $map['活动预置名称'] = BonusOper::ACT_NAME;
        $map['当前工作人员'] = implode('，', BonusOper::getWorkers());
        $map['内网权限'] = implode('，', UserOper::reg());
        $map['当前吧务组'] = '第' . WxOrg::year . '届';
        $tables = Db::query('SHOW TABLES;');
        $Tables_in_hanbj = [];
        foreach ($tables as $item) {
            $tmp = $item['Tables_in_hanbj'];
            $Tables_in_hanbj[] = $tmp;
            $tabledesc = Db::query("DESC `hanbj`.`$tmp`");
            $tablename = [];
            foreach ($tabledesc as $item2) {
                $tablename[] = $item2['Field'];
            }
            $map["Table $tmp"] = implode(', ', $tablename);
        }
        $map['Tables'] = implode(', ', $Tables_in_hanbj);
        return view('token', ['data' => $map]);
    }

    public function test()
    {
        $org = new WxOrg();
        $ans = $org->getAns();
        $ratio = count($ans) * 100.0 / count($org->getAll()) / count($org->obj);
        $ratio = number_format($ratio, 2, '.', '');
        $miss = cache(WxOrg::name . 'getAns.miss');
        $map['unique_name'] = ['in', $org->obj];
        $ret = Db::table('member')
            ->where($map)
            ->field([
                'unique_name as u',
                'tieba_id as t'
            ])
            ->select();
        $dict = [];
        foreach ($ret as $t) {
            $dict[$t['u']] = $t['t'];
        }
        $ans = [
            'avg' => $org->getAvg($ans),
            'cmt' => $org->getComment($ans),
            'obj' => $org->obj,
            'trn' => $dict,
            'mis' => $miss,
            'rto' => $ratio
        ];
        return view('test', ['data' => json_encode($ans)]);
    }

    public function debug()
    {
        MemberOper::Normal2Junior('夏癸未');
    }
}