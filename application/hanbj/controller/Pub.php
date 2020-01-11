<?php

namespace app\hanbj\controller;

use Endroid\QrCode\Exceptions\ImageFunctionFailedException;
use Endroid\QrCode\Exceptions\ImageFunctionUnknownException;
use hanbj\BonusOper;
use hanbj\FameOper;
use hanbj\HBConfig;
use hanbj\UserOper;
use hanbj\vote\WxVote;
use think\Controller;
use Endroid\QrCode\QrCode;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\Response;
use think\response\Json;


class Pub extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/pub_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    /**
     * @return Response|Json
     * @throws ImageFunctionFailedException
     * @throws ImageFunctionUnknownException
     */
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
        return json(['msg' => 'limited']);
//        $size = input('post.limit', 20, FILTER_VALIDATE_INT);
//        $offset = input('post.offset', 0, FILTER_VALIDATE_INT);
//        $size = min(100, max(0, $size));
//        $offset = max(0, $offset);
//        $search = input('post.search');
//        if (!empty($search)) {
//            $map['m.unique_name'] = ['like', '%' . $search . '%'];
//        }
//        $map['m.code'] = ['in', MemberOper::getMember()];
//        $join = [
//            ['nfee f', 'm.unique_name=f.unique_name', 'left']
//        ];
//        $tmp = Db::table('member')
//            ->alias('m')
//            ->join($join)
//            ->where($map)
//            ->order('m.id')
//            ->limit($offset, $size)
//            ->cache(600)
//            ->group('m.unique_name')
//            ->field([
//                'm.unique_name as u',
//                'm.year_time as t',
//                'sum(f.code) as b'
//            ])
//            ->select();
//        $data['rows'] = $tmp;
//        $total = Db::table('member')
//            ->alias('m')
//            ->where($map)
//            ->cache(600)
//            ->count();
//        $data['total'] = $total;
//        return json($data);
    }

    public function json_fame()
    {
        $data = FameOper::getOrder();
        return json($data);
    }

    public function json_bonus()
    {
        $tmp = BonusOper::getTop();
        return json($tmp);
    }

    /**
     * @param int $year
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function json_vote($year = HBConfig::YEAR)
    {
        $year = intval($year);
        $ans = WxVote::getResult($year);
        return json($ans);
    }
}
