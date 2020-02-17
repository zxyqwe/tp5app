<?php

namespace app\index\controller;

use hanbj\HBConfig;
use hanbj\UserOper;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Controller;
use think\exception\HttpResponseException;

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
        if (is_file(__DIR__ . "/../tpl/develop_$action.html")) {
            throw new HttpResponseException(view($action));
        }
        abort(404, "页面不存在$action");
    }

    public function show()
    {
        try {
            $ossClient = new OssClient(config('oss_key'), config('oss_sk'), config('oss_end'));
            $options = array(
                'delimiter' => '/',
                'prefix' => 'video/bilibili/av',
                'max-keys' => 1000,
                'marker' => '',
            );
            $listObjectInfo = $ossClient->listObjects(config('oss_buk'), $options);
            $objectList = $listObjectInfo->getObjectList(); // object list
            $prefixList = $listObjectInfo->getPrefixList(); // directory list
            $oname = [];
            foreach ($objectList as $objectInfo) {
                $oname[] = $objectInfo->getKey();
            }
            $pname = [];
            foreach ($prefixList as $prefixInfo) {
                $pname[] = $prefixInfo->getPrefix();
            }
            return json(['oname' => $oname, 'pname' => $pname]);
        } catch (OssException $e) {
            throw new HttpResponseException(json(['msg' => $e->getMessage()], 400));
        }
    }
}