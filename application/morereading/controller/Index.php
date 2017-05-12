<?php

namespace app\morereading\controller;

class Index
{
    public function index()
    {
        return [];
    }

    public function login()
    {
        return [];
    }

    public function isbn()
    {
        $isbn = input('post.code');
        $isbn = isbn_clean($isbn);
        if (!isbn_validate13($isbn)) {
            exception('isbn ' . $isbn, 10001);
        }
        if (cache('?' . $isbn)) {
            return json_decode(cache($isbn), true);
        }
        $req = aws_build($isbn);
        $res = Curl_Get($req);
        $res = simplexml_load_string($res);
        $res = json_decode(json_encode($res), true);
        if (array_key_exists('Items', $res) && array_key_exists('Item', $res['Items'])) {
            $res = $res['Items']['Item'];
        } else {
            return $res;
        }
        if (!array_key_exists('ASIN', $res)) {
            $data = array();
            foreach ($res as $re) {
                $data[] = extract_aws($re);
            }
        } else {
            $data = extract_aws($res);
        }
        if (!empty($data)) {
            cache($isbn, json_encode($data));
        }
        return $data;
    }
}
