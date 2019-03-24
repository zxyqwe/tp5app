<?php

namespace bilibili;

use thiagoalessio\TesseractOCR\TesseractOCR;
use util\MysqlLog;

class BiliSilver extends BiliBase
{
    public function silver()
    {
        if ($this->lock('day_empty')) {
            return;
        }
        $data = $this->silverTask();
        if (empty($data)) {
            return;
        }
        $data = json_decode($data, true);
        $start = $data['data']['time_start'];
        $end = $data['data']['time_end'];
        if (time() < $end) {
            return;
        }
        $captcha = $this->captcha();
        if ($captcha === false) {
            return;
        }
        $urlapi = $this->prefix . "lottery/v1/SilverBox/getAward?time_start=$start&time_end=$end&captcha=$captcha";
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        if ($data['code'] === 0) {
            trace("领取银瓜子：{$data['data']['silver']} (+{$data['data']['awardSilver']})", MysqlLog::INFO);
            $this->lock('silverTask', -1);
            $this->silverTask();
        } else {
            if ('访问被拒绝' === $data['msg']) {
                return;
            }
            if (-903 === $data['code'] || false !== strpos($data['msg'], '过期')) {
                trace("领取失败：{$data['msg']}", MysqlLog::ERROR);
                $this->lock('silverTask', -1);
                $this->silverTask();
                return;
            }
            trace("领取失败：$res", MysqlLog::ERROR);
        }
    }

    private function silverTask()
    {
        if ($this->lock('silverTask')) {
            return $this->lock('silverTask', 1);
        }
        $urlapi = $this->prefix . 'lottery/v1/SilverBox/getCurrentTask';
        $res = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($res, true);
        switch ($data['code']) {
            case -101:
                trace("silverTask $res", MysqlLog::ERROR);
                return '';
            case -10017:
                trace("day empty {$data['msg']}", MysqlLog::ERROR);
                $this->lock('day_empty', $this->long_timeout());
                return '';
        }
        return $this->lock('silverTask', 1, $res);
    }

    private function captcha()
    {
        $urlapi = $this->prefix . 'lottery/v1/SilverBox/getCaptcha?ts=' . time();
        $raw = $this->bili_Post($urlapi, $this->cookie, $this->room_id);
        $data = json_decode($raw, true);
        if ($data['code'] !== 0) {
            trace("captcha $raw", MysqlLog::ERROR);
            return false;
        }
        $data = $data['data']['img'];
        return $this->ocr($data);
    }

    public function ocr($data)
    {
        $raw = substr($data, strpos($data, 'base64,') + 7);
        $raw = base64_decode($raw);
        $image = imagecreatefromstring($raw);
        if ($image === false) {
            trace("captcha image $raw", MysqlLog::ERROR);
            return false;
        }
        $file_path = '/tmp/bilibili_ocr.png';
        imagepng($image, $file_path);
        imagedestroy($image);
        $result = (new TesseractOCR($file_path))
            ->whitelist(range(0, 9), '+-')
            ->run();
        $ans = eval("return $result;");
        return $ans;
    }
}
