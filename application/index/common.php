<?php

namespace app\index;

class GeoHelper
{
    private $ak = '';

    public function __construct()
    {
        $this->ak = config('baidu_ak');
    }

    public function getPos($pos)
    {
        $prefix = 'baidu_' . $pos;
        if (cache("?$prefix")) {
            return cache($prefix);
        }
        if (cache('?baidu_limit_geopos')) {
            return json_encode(['msg' => 1]);
        }
        $urlapi = "http://api.map.baidu.com/geocoder/v2/?address=$pos&output=json&city=北京市&ak={$this->ak}";
        $raw = Curl_Get($urlapi);
        $data = json_decode($raw, true);
        if ($data['status'] === 0) {
            cache($prefix, $raw);
        } elseif ($data['status'] === 302) {
            cache('baidu_limit_geopos', 'baidu_limit_geopos', 8 * 3600);
            return json_encode(['msg' => 1]);
        }
        return $raw;
    }
}
