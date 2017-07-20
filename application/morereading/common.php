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