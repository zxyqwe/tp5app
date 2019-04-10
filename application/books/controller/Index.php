<?php

namespace app\books\controller;

use books\BConfig;
use Endroid\QrCode\QrCode;
use think\Controller;
use think\exception\HttpResponseException;
use util\MysqlLog;

class Index extends Controller
{
    const VERSION = 'books_login';
    const time = 60;

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/index_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function index()
    {
        if (self::VERSION === session('login')) {
            return redirect('https://app.zxyqwe.com/books/index/home');
        }
        return view('login');
    }

    public function json_login()
    {
        switch ($this->request->method()) {
            case 'GET':
                $nonce = getNonceStr();
                session('nonce', $nonce);
                cache("jump$nonce", json_encode(['event' => 'login']), self::time);
                $qrCode = new QrCode("https://app.zxyqwe.com/books/index/wx/obj/$nonce");
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
                    if (self::VERSION === $nonce['login']) {
                        session('login', self::VERSION);
                        session('unique_name', $nonce['uni']);
                        return json();
                    }
                }
                return json(['msg' => $nonce], 400);
            default:
                return json(['msg' => $this->request->method()], 400);
        }
    }

    public function wx($obj)
    {
        $unique = session('unique_name');
        if (in_array($unique, BConfig::valid_user)) {
            return $this->wx_ok($obj, $unique);
        } else if (input('?get.code')) {
            $api = config('hanbj_api');
            $sec = config('hanbj_secret');
            $openid = WX_code(input('get.code'), $api, $sec);
            if (!is_string($openid)) {
                trace("wx_login " . json_encode($openid), MysqlLog::ERROR);
            } else if (in_array($openid, BConfig::valid_user)) {
                return $this->wx_ok($obj, $openid);
            } else {
                trace("尝试登陆 $openid", MysqlLog::INFO);
                return redirect("https://www.baidu.com");
            }
        }
        $prefix = empty($obj) ? '' : '/wx/obj/' . $obj;
        return WX_redirect('https://app.zxyqwe.com/books/index/wx' . $prefix, config('hanbj_api'));
    }

    private function wx_ok($obj, $unique)
    {
        if (!empty($obj)) {
            $data = ['login' => self::VERSION, 'uni' => $unique];
            cache("login$obj", json_encode($data), self::time * 2);
        }
        session('login', self::VERSION);
        session('unique_name', $unique);
        trace("$unique 登录网页", MysqlLog::INFO);
        return redirect('https://app.zxyqwe.com/books/index/home');
    }
}
