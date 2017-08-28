<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function cross_site()
{
    if (input('server.HTTP_HOST') === '??') {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $refer = $_SERVER['HTTP_REFERER'];
            $url = parse_url($refer);
            header("Access-Control-Allow-Origin:" .
                $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : ''));
        } else {
            header("Access-Control-Allow-Origin:*");
        }
    } else {
        header("Access-Control-Allow-Origin:https://??");
    }
}

function Curl_Post($curlPost, $url, $easy = true)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $easy ? http_build_query($curlPost) : json_encode($curlPost, JSON_UNESCAPED_UNICODE));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);
    $return_str = curl_exec($curl);
    if ($return_str === false) {
        $num = curl_errno($curl);
        $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($curl);
        trace(json_encode(array('method' => 'post', 'url' => $url, 'param' => $curlPost, 'res' => $return_str)));
    }
    curl_close($curl);
    return $return_str;
}

function bili_Post($url, $cookie, $room)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_REFERER, 'http://live.bilibili.com/' . $room);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36');
    $return_str = curl_exec($curl);
    if ($return_str === false) {
        $num = curl_errno($curl);
        $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($curl);
        trace(json_encode(array('url' => $url, 'res' => $return_str)));
    }
    curl_close($curl);
    return $return_str;
}

function Curl_Get($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);
    $return_str = curl_exec($curl);
    if ($return_str === false) {
        $num = curl_errno($curl);
        $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($curl);
        trace(json_encode(array('method' => 'get', 'url' => $url, 'res' => $return_str)));
    }
    curl_close($curl);
    return $return_str;
}

function getNonceStr($length = 32, $chars = "abcdefghijklmnopqrstuvwxyz0123456789")
{
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

function WX_union($access_token, $openid)
{
    /*
    * {"errcode":42001,"errmsg":"access_token expired, hints: [ req_id: rabp.A0077ns41 ]"}
     * {"openid":"ov3ult_HuJrCd8GjaC6HaPkLRFUU","nickname":"shiyuka","sex":1,"language":"zh_CN","city":"Hebei","province":"Tianjin","country":"CN","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/aPSbIhPARHAaWgYobs1H3m8OEaFoZiaVibRJnoFqDJFwUDqQE5KiaZichyst3Iq5pNia8dpbJugEHbdIm5RkentqQ1zoFzDicotXGb\/0","privilege":[],"unionid":"ohT2YuN9miDm3ZSKnnSuamgsw4qw"}
    * 和openid无关，只用accesstoken
    */
    $data = Curl_Get("https://api.weixin.qq.com/sns/userinfo?" .
        "access_token=" . $access_token .
        "&openid=" . $openid .
        "&lang=zh_CN");
    $data = json_decode($data, true);
    if (!isset($data['openid'])) {
        trace("Weixin Exception " . json_encode($data));
        return $data;
    }
    return $data['openid'];
}

function WX_code($code, $api, $sec)
{
    $res = Curl_Get('https://api.weixin.qq.com/sns/oauth2/access_token?' .
        'appid=' . $api .
        '&secret=' . $sec .
        '&code=' . $code .
        '&grant_type=authorization_code');
    $res = json_decode($res, true);
    if (!isset($res['access_token']) || !isset($res['openid'])) {
        return $res;
    }
    trace("Weixin Code " . $res['openid']);
    return $res['openid'];
}

function WX_redirect($uri, $api, $state = '')
{
    return redirect('https://open.weixin.qq.com/connect/oauth2/authorize?' .
        'appid=' . $api .
        '&redirect_uri=' . urlencode($uri) .
        '&response_type=code' .
        '&scope=snsapi_base' .
        '&state=' . $state .
        '#wechat_redirect');
}

function WX_access($api, $sec, $name)
{
    $tmp = cache($name);
    if (false !== $tmp)
        return $tmp;
    $res = Curl_Get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $api . '&secret=' . $sec);
    $res = json_decode($res, true);
    if (!isset($res['access_token']) || !isset($res['expires_in'])) {
        return $res;
    }
    trace("Weixin Access " . $res['access_token']);
    cache($name, $res['access_token'], intval($res['expires_in']));
    return $res['access_token'];
}

function WX_iter($api, $sec)
{
    if (session('?openid')) {
        return true;
    }
    if (input('?get.code')) {
        $openid = WX_code(input('get.code'), $api, $sec);
        if (!is_array($openid)) {
            session('openid', $openid);
            return true;
        }
    }
    return false;
}

\think\Route::rule('index.php/hyb', 'hanbj/index/old');