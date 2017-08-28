<?php

namespace app\index\controller;

use Endroid\QrCode\QrCode;

class Index
{
    public function _empty()
    {
        return '';
    }

    public function index()
    {
        $qrCode = new QrCode('Life is too short to be generating QR codes');
        $qrCode
            ->setSize(300)
            ->setErrorCorrection(QrCode::LEVEL_HIGH)
            ->setLogo($qrCode->getImagePath() . DS . 'logo.png')
            ->setLogoSize(150)
            ->setLabelFontPath(APP_PATH . "../public/static/noto_sans.otf")
            ->setLabelFontSize(25)
            ->setLabel("中文asd");
        return response($qrCode->get(QrCode::IMAGE_TYPE_PNG), 200, [
            'Cache-control' => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0",
            'Content-Type' => "image/png; charset=utf-8"
        ]);
    }

    public function bilibili()
    {
        return json(['past' => cache('cron_user_past'), 'cur' => cache('cron_user')]);
    }

    public function cron()
    {
        if (cache('?cron_cookie')) {
            return json(['msg' => 'too fast']);
        }
        cache('cron_cookie', 'cron_cookie', 290);
        $cookie = config('cron_cookie');
        $urlapi = 'https://api.live.bilibili.com/User/userOnlineHeart';
        $res = bili_Post($urlapi, $cookie, 218);
        $res = json_decode($res, true);
        if ($res['code'] !== 0) {
            trace(json_encode($res));
        }
        cache('cron_user_past', cache('cron_user'));
        $urlapi = 'http://live.bilibili.com/User/getUserInfo';
        $res = bili_Post($urlapi, $cookie, 218);
        cache('cron_user', $res);
        return json(['msg' => 'ok']);
    }
}
