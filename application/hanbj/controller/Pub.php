<?php

namespace app\hanbj\controller;

use app\hanbj\BonusOper;
use app\hanbj\MemberOper;
use app\hanbj\UserOper;
use think\Controller;
use think\Db;
use Endroid\QrCode\QrCode;
use think\exception\HttpResponseException;


class Pub extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/pub_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, '页面不存在', [$action]);
    }

    public function json_login()
    {
        switch ($this->request->method()) {
            case 'GET':
                $nonce = getNonceStr();
                session('nonce', $nonce);
                cache("jump$nonce", json_encode(['event' => 'login']), UserOper::time);
                $qrCode = new QrCode("https://app.zxyqwe.com/hanbj/mobile/index/obj/$nonce");
                $qrCode
                    ->setSize(200)
                    ->setErrorCorrection(QrCode::LEVEL_HIGH)
                    ->setLabelFontPath(APP_PATH . "../public/static/noto_sans.otf")
                    ->setLabelFontSize(15)
                    ->setLabel("微信扫码登录");
                return response($qrCode->get(QrCode::IMAGE_TYPE_JPEG), 200, [
                    'Cache-control' => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0",
                    'Content-Type' => "image/jpeg; charset=utf-8"
                ]);
            case 'POST':
                $nonce = session('nonce');
                $nonce = cache("login$nonce");
                if (strlen($nonce) > 5) {
                    $nonce = json_decode($nonce, true);
                    if (UserOper::VERSION === $nonce['login']) {
                        session('login', UserOper::VERSION);
                        session('name', $nonce['uni']);
                        session('unique_name', $nonce['uni']);
                        return json();
                    }
                }
                return json(['msg' => $nonce], 400);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    public function json_bulletin()
    {
        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
        $size = min(100, max(0, $size));
        $offset = max(0, $offset);
        $search = input('post.search');
        if (!empty($search)) {
            $map['m.unique_name'] = ['like', '%' . $search . '%'];
        }
        $map['m.code'] = ['in', MemberOper::getMember()];
        $join = [
            ['nfee f', 'm.unique_name=f.unique_name', 'left']
        ];
        $tmp = Db::table('member')
            ->alias('m')
            ->join($join)
            ->where($map)
            ->order('m.id')
            ->limit($offset, $size)
            ->cache(600)
            ->group('m.unique_name')
            ->field([
                'm.unique_name as u',
                'm.year_time as t',
                'sum(f.code) as b'
            ])
            ->select();
        $data['rows'] = $tmp;
        $total = Db::table('member')
            ->alias('m')
            ->where($map)
            ->cache(600)
            ->count();
        $data['total'] = $total;
        return json($data);
    }

    public function json_fame()
    {
        $join = [
            ['member m', 'm.unique_name=f.unique_name', 'left']
        ];
        $res = Db::table('fame')
            ->alias('f')
            ->join($join)
            ->order('year desc,grade')
            ->field([
                'f.unique_name',
                'tieba_id',
                'year',
                'grade',
                'label'
            ])
            ->select();
        $data = [];
        foreach ($res as $item) {
            $year = $item['year'];
            if (!isset($data[$year])) {
                $data[$year] = ['name' => $year];
                $data[$year]['teams'] = [];
            }
            $team = $item['label'];
            if (!isset($data[$year]['teams'][$team])) {
                $data[$year]['teams'][$team] = ['name' => $team];
                $data[$year]['teams'][$team]['ms'] = [];
            }
            $data[$year]['teams'][$team]['ms'][] = [
                'u' => $item['unique_name'],
                't' => $item['tieba_id'],
                'id' => $item['grade']
            ];
        }
        $data = array_values($data);
        foreach ($data as &$item) {
            $item['teams'] = array_values($item['teams']);
        }
        return json($data);
    }

    public function json_bonus()
    {
        $tmp = BonusOper::getTop();
        return json($tmp);
    }
}