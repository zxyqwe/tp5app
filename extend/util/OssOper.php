<?php

namespace util;

use bilibili\BiliOssFile;
use OSS\Core\OssException;
use OSS\OssClient;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class OssOper
{
    private $client_;
    private $client_public_;

    /**
     * OssOper constructor.
     * @throws OssException
     */
    function __construct()
    {
        $this->client_ = new OssClient(config('oss_key'), config('oss_sk'), config('oss_end'));
        $this->client_public_ = new OssClient(config('oss_key'), config('oss_sk'), config('oss_end_pub'));
    }

    /**
     * @param $position
     * @param $filePath
     * @throws OssException
     */
    public function uploadBackups($position, $filePath)
    {
        $this->client_->uploadFile(config('oss_buk'), "backups/" . $position, $filePath);
    }

    /**
     * @return array
     * @throws OssException
     */
    public function getVideoDir()
    {
        $nextMarker = '';
        $ret = [];
        while (true) {
            $options = array(
                'delimiter' => '/',
                'prefix' => 'video/bilibili/av',
                'max-keys' => 1000,
                'marker' => $nextMarker,
            );
            $listObjectInfo = $this->client_->listObjects(config('oss_buk'), $options);
            $nextMarker = $listObjectInfo->getNextMarker();
            $prefixList = $listObjectInfo->getPrefixList(); // directory list
            foreach ($prefixList as $prefixInfo) {
                $ret[] = $prefixInfo->getPrefix();
            }
            if ($nextMarker === '') {
                break;
            }
        }
        return $ret;
    }

    /**
     * @return array
     * @throws OssException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getVideoFile()
    {
        $kvmap = BiliOssFile::getAllData();
        $used_keys = [];
        $nextMarker = '';
        $ret = [];
        while (true) {
            $options = array(
                'delimiter' => '',
                'prefix' => 'video/bilibili/av',
                'max-keys' => 1000,
                'marker' => $nextMarker,
            );
            $listObjectInfo = $this->client_->listObjects(config('oss_buk'), $options);
            $nextMarker = $listObjectInfo->getNextMarker();
            $objectList = $listObjectInfo->getObjectList(); // object list
            foreach ($objectList as $objectInfo) {
                $oname = $objectInfo->getKey();
                if (self::endsWith($oname, '/') || self::endsWith($oname, 'xml')) {
                    continue;
                }
                $oname = explode('/', $oname);
                $oname = array_reverse($oname);
                $av = $oname[1];
                $target_meta = [];
                if (array_key_exists($av, $kvmap)) {
                    $target_meta = $kvmap[$av];
                    // av l p t
                }
                $used_keys[] = $av;
                $ret[] = array_merge([
                    'id' => $av,
                    'n' => $oname[0],
                    's' => self::readableSize($objectInfo->getSize()),
                    'e' => $objectInfo->getETag(),
                    'm' => $objectInfo->getLastModified(),
                    'c' => $objectInfo->getStorageClass(),
                    'pt' => $objectInfo->getType()
                ], $target_meta);
            }
            if ($nextMarker === '') {
                break;
            }
        }
        foreach ($used_keys as $item) {
            unset($kvmap[$item]);
        }
        foreach ($kvmap as $k => $v) {
            $ret[] = array_merge([
                'id' => $k
            ], $v);
        }
        return $ret;
    }

    /**
     * @param $dir
     * @param $name
     * @return string
     * @throws OssException
     */
    public function getUrl($dir, $name)
    {
        $object = "video/bilibili/$dir/$name";
        $timeout = 3600;
        $signedUrl = $this->client_public_->signUrl(config('oss_buk'), $object, $timeout);
        return $signedUrl;
    }

    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    private static function readableSize($size)
    {
        $a = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, 2) . " " . $a[$pos];
    }
}