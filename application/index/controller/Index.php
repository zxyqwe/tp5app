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
        return response($qrCode->get(QrCode::IMAGE_TYPE_PNG), 200,
            ['Cache-control' => "no-store, no-cache, must-revalidate, post-check=0, pre-check=0"],
            "image/png");
    }
}
