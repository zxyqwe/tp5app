<?php

namespace hanbj\weixin;

use wxsdk\WxPayOrderQuery;
use wxsdk\WxPayApi;
use wxsdk\WxPayNotify;
use hanbj\OrderOper;

class HanbjNotify extends WxPayNotify
{
    public function Queryorder($out_trade_no)
    {
        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($out_trade_no);
        $result = WxPayApi::orderQuery(new HanbjPayConfig(), $input);
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
        ) {
            return true;
        }
        return false;
    }

    public function NotifyProcess($data, $config, &$msg)
    {
        $msg = 'OK';
        $data = $data->GetValues();
        if (!array_key_exists("out_trade_no", $data)) {
            return false;
        }
        //查询订单，判断订单真实性
        if (!$this->Queryorder($data["out_trade_no"])) {
            return false;
        }
        return OrderOper::handle($data);
    }
}