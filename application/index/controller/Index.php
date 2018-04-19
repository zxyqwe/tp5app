<?php

namespace app\index\controller;

use Endroid\QrCode\QrCode;

class Index
{
    public function _empty()
    {
        return json([], 404);
    }

    public function index()
    {
        $qrCode = new QrCode('https://app.zxyqwe.com/index/index/index');
        $qrCode
            ->setSize(300)
            ->setErrorCorrection(QrCode::LEVEL_HIGH)
            ->setLogo($qrCode->getImagePath() . DS . 'logo.png')
            ->setLogoSize(50)
            ->setLabelFontPath(APP_PATH . "../public/static/noto_sans.otf")
            ->setLabelFontSize(25)
            ->setLabel("中文asd");
        return response($qrCode->get(QrCode::IMAGE_TYPE_PNG), 200, [
            'Cache-control' => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0",
            'Content-Type' => "image/png; charset=utf-8"
        ]);
    }

    public function github()
    {
        $sk = '688787d8ff144c502c7f5cffaafe2cc588d86079f9de88304c26b0cb99ce91c6';
        $post_data = file_get_contents('php://input');
        $signature = 'sha1=' . hash_hmac('sha1', $post_data, $sk);
        $gh = $_SERVER['HTTP_X_HUB_SIGNATURE'];
        if ($gh !== $signature) {
            return json(["$gh $signature"], 400);
        }
        return json();
    }
}
