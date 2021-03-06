<?php

namespace app\index\controller;

use Endroid\QrCode\QrCode;
use PHPMailer\PHPMailer\Exception;
use think\Controller;
use think\exception\HttpResponseException;
use util\BackupOper;
use util\MysqlLog;

class Index extends Controller
{
    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../view/index_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        return json([], 404);
    }

    public function qrcode()
    {
        local_cron();
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
        $post_data = file_get_contents('php://input');
        $signature = 'sha1=' . hash_hmac('sha1', $post_data, config('github_sk'));
        $gh = $_SERVER['HTTP_X_HUB_SIGNATURE'];
        if ($gh !== $signature) {
            return json(["$gh $signature"], 400);
        }
        return json();
    }

    public function amail()
    {
        local_cron();
        define('TAG_TIMEOUT_EXCEPTION', true);
        $to = input('post.to');
        $sub = input('post.sub');
        $main = input('post.main');
        $sign = input('post.sign');
        if ($sign !== md5($to . $sub . $main . config('amail_sk'))) {
            return json('a', 400);
        }
        if (cache("?amail$to$sub")) {
            return json('d', 400);
        }
        $mail = BackupOper::getMail();                              // Passing `true` enables exceptions
        try {
            $mail->addAddress($to);
            //Content
            $mail->Subject = $sub;
            $mail->Body = $main;
            $mail->send();
            cache("amail$to$sub", 'a', 600);
            return json('c');
        } catch (Exception $e) {
            trace($mail->ErrorInfo, MysqlLog::ERROR);
            return json('b', 400);
        }
    }
}
