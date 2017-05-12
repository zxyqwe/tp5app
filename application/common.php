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
function aws_build($isbn)
{
    $aws_access_key_id = config('aws_access_key_id');
    $aws_secret_key = config('aws_secret_key');
    $endpoint = config('aws_end_point');
    $uri = config('aws_uri');

    $params = array(
        "Service" => "AWSECommerceService",
        "Operation" => "ItemLookup",
        "AWSAccessKeyId" => $aws_access_key_id,
        "AssociateTag" => "zxyqwe",
        "ItemId" => $isbn,
        "IdType" => "ISBN",
        "ResponseGroup" => "Large",
        "SearchIndex" => "All",
        "Timestamp" => gmdate('Y-m-d\TH:i:s\Z')
    );

    ksort($params);
    $pairs = array();

    foreach ($params as $key => $value) {
        array_push($pairs, rawurlencode($key) . "=" . rawurlencode($value));
    }
    $canonical_query_string = join("&", $pairs);
    $string_to_sign = "GET\n" . $endpoint . "\n" . $uri . "\n" . $canonical_query_string;
    $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $aws_secret_key, true));
    $request_url = 'http://' . $endpoint . $uri . '?' . $canonical_query_string . '&Signature=' . rawurlencode($signature);
    return $request_url;
}

function isbn_validate10($isbn)
{
    if (strlen($isbn) != 10 || !is_numeric(substr($isbn, 0, 9))) {
        return false;
    }
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        if ($isbn[$i] == 'X') {
            $sum += 10 * (10 - $i);
        } else if (is_numeric($isbn[$i])) {
            $sum += $isbn[$i] * (10 - $i);
        } else {
            return false;
        }
    }

    return $sum % 11 == 0;
}

function isbn_validate13($isbn)
{
    if (strlen($isbn) != 13 || !is_numeric($isbn)) {
        return false;
    }
    $i = $isbn;
    $sum = 3 * ($i[1] + $i[3] + $i[5] + $i[7] + $i[9] + $i[11])
        + $i[0] + $i[2] + $i[4] + $i[6] + $i[8] + $i[10];
    return $i[12] == (10 - $sum % 10) % 10;
}

function isbn_to10($isbn)
{
    $isbn = isbn_clean($isbn);
    if (strlen($isbn) == 10) {
        return $isbn;
    } else if (strlen($isbn) != 13 || substr($isbn, 0, 3) != '978') {
        return false;
    }
    if (!isbn_validate13($isbn)) {
        return false;
    }
    $i = substr($isbn, 3);
    $sum = $i[0] * 1 + $i[1] * 2 + $i[2] * 3 + $i[3] * 4 + $i[4] * 5
        + $i[5] * 6 + $i[6] * 7 + $i[7] * 8 + $i[8] * 9;
    $check = $sum % 11;
    if ($check == 10) {
        $check = "X";
    }

    return substr($isbn, 3, 9) . $check;
}

function isbn_to13($isbn)
{
    $isbn = isbn_clean($isbn);
    if (strlen($isbn) == 13) {
        return $isbn;
    } else if (strlen($isbn) != 10) {
        return false;
    }
    if (!isbn_validate10($isbn)) {
        return false;
    }
    $i = "978" . substr($isbn, 0, -1);
    $sum = 3 * ($i[1] + $i[3] + $i[5] + $i[7] + $i[9] + $i[11])
        + $i[0] + $i[2] + $i[4] + $i[6] + $i[8] + $i[10];
    $check = $sum % 10;
    if ($check != 0) {
        $check = 10 - $check;
    }
    return $i . $check;
}

function isbn_clean($isbn)
{
    return preg_replace("/[^0-9X]+/", '', $isbn);
}

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

function Curl_Post($curlPost, $url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($curlPost));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($curl, CURLOPT_TIMEOUT, 3);
    $return_str = curl_exec($curl);
    if ($return_str === false) {
        $num = curl_errno($curl);
        $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($curl);
    }
    curl_close($curl);
    trace(json_encode(array('method' => 'post', 'url' => $url, 'param' => $curlPost)));
    return $return_str;
}

function Curl_Get($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($curl, CURLOPT_TIMEOUT, 3);
    $return_str = curl_exec($curl);
    if ($return_str === false) {
        $num = curl_errno($curl);
        $return_str .= $num . ':' . curl_strerror($num) . ':' . curl_error($curl);
    }
    curl_close($curl);
    trace(json_encode(array('method' => 'get', 'url' => $url)));
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

function WX($access_token, $openid)
{
    /*
    * {"errcode":42001,"errmsg":"access_token expired, hints: [ req_id: rabp.A0077ns41 ]"}
     * {"openid":"ov3ult_HuJrCd8GjaC6HaPkLRFUU","nickname":"shiyuka","sex":1,"language":"zh_CN","city":"Hebei","province":"Tianjin","country":"CN","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/aPSbIhPARHAaWgYobs1H3m8OEaFoZiaVibRJnoFqDJFwUDqQE5KiaZichyst3Iq5pNia8dpbJugEHbdIm5RkentqQ1zoFzDicotXGb\/0","privilege":[],"unionid":"ohT2YuN9miDm3ZSKnnSuamgsw4qw"}
    * 和openid无关，只用accesstoken
    */
    $data = Curl_Get("https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN");
    $data = json_decode($data, true);
    if (isset($data['errcode'])) {
        trace($data['errcode']);
        exception($data['errcode'], 10001);
    } elseif (!isset($data['openid'])) {
        trace("Weixin Exception " . json_encode($data));
        exception(json_encode($data), 10002);
    }
    return $data['openid'];
}

function WX_code($code)
{
    $res = Curl_Get('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . config('wx_js_api') . '&secret=' . config('ws_js_secret') . '&code=' . $code . '&grant_type=authorization_code');
    $res = json_decode($res, true);
    if (!isset($res['access_token']) || !isset($res['openid'])) {
        exception(json_encode($res), 10003);
    }
    trace("Weixin Code " . $res['openid']);
    return $res['openid'];
}

function extract_aws($res)
{
    $data = array();
    if (array_key_exists('EditorialReviews', $res)) {
        $data['EditorialReviews'] = $res['EditorialReviews'];
    }
    if (array_key_exists('ItemAttributes', $res)) {
        $data['ItemAttributes'] = $res['ItemAttributes'];
    }
    if (array_key_exists('LargeImage', $res)) {
        $data['LargeImage'] = $res['LargeImage'];
    }
    return $data;
}
