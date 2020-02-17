<?php

namespace util;

use OSS\Core\OssException;
use OSS\OssClient;

class OssOper
{
    private $client_;

    /**
     * OssOper constructor.
     * @throws OssException
     */
    function __construct()
    {
        $this->client_ = new OssClient(config('oss_key'), config('oss_sk'), config('oss_end'));
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
                $ret[] = [
                    'n' => $oname,
                    's' => $objectInfo->getSize(),
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

    private static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}