<?php

namespace util;

use OSS\Core\OssException;
use OSS\OssClient;

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
     */
    public function getVideoFile()
    {
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
                $ret[] = [
                    'id' => $oname[1],
                    'n' => $oname[0],
                    's' => self::readableSize($objectInfo->getSize()),
                    'e' => $objectInfo->getETag(),
                    'm' => $objectInfo->getLastModified(),
                    'c' => $objectInfo->getStorageClass(),
                    't' => $objectInfo->getType()
                ];
            }
            if ($nextMarker === '') {
                break;
            }
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