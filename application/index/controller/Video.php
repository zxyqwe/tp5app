<?php

namespace app\index\controller;

use hanbj\HBConfig;
use hanbj\UserOper;
use OSS\Core\OssException;
use think\Controller;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\HttpResponseException;
use think\response\Json;
use util\OssOper;

class Video extends Controller
{
    protected $beforeActionList = [
        'coder',
    ];

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
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

    /**
     * @return Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
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

    public function geturl()
    {
        $dir = input('post.dir');
        $name = input('post.name');
        try {
            $oss_client = new OssOper();
            $url = $oss_client->getUrl($dir, $name);
            $type = 'video/mp4';
            if (OssOper::endsWith($name, 'flv')) {
                $type = 'video/x-flv';
            }
            return json(['url' => $url, 'type' => $type]);
        } catch (OssException $e) {
            throw new HttpResponseException(json(['msg' => $e->getMessage()], 400));
        }
    }
}