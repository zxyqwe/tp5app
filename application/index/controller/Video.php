<?php

namespace app\index\controller;

use hanbj\HBConfig;
use hanbj\UserOper;
use OSS\Core\OssException;
use think\Controller;
use think\exception\HttpResponseException;
use util\OssOper;

class Video extends Controller
{
    protected $beforeActionList = [
        'coder',
    ];

    protected function coder()
    {
        if (request()->ip() === config('local_mech')) {
            return;
        }
        UserOper::valid_pc($this->request->isAjax());
        if (session('name') === HBConfig::CODER) {
            return;
        }
        if (request()->isAjax()) {
            $res = json(['msg' => '没有权限'], 400);
        } else {
            $res = redirect('https://app.zxyqwe.com/hanbj/index/home');
        }
        throw new HttpResponseException($res);
    }

    public function _empty()
    {
        $action = $this->request->action();
        if (is_file(__DIR__ . "/../tpl/video_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function json_show()
    {
        try {
            $oss_client = new OssOper();
            $video_list = $oss_client->getVideoFile();
            return json($video_list);
        } catch (OssException $e) {
            throw new HttpResponseException(json(['msg' => $e->getMessage()], 400));
        }
    }
}