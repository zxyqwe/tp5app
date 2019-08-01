<?php

namespace hanbj\weixin;

use util\MysqlLog;
use wxsdk\pay\WxPayOrderQuery;
use wxsdk\pay\WxPayApi;
use wxsdk\pay\WxPayNotify;
use hanbj\OrderOper;
use Exception;

class HanbjNotify extends WxPayNotify
{
    public function Queryorder($out_trade_no)
    {
        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($out_trade_no);
        $result = WxPayApi::orderQuery(new HanbjPayConfig(), $input);
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] === "SUCCESS"
            && $result["result_code"] === "SUCCESS"
        ) {
            if (array_key_exists("trade_state", $result)
                && $result['trade_state'] === "SUCCESS"
            ) {
                return true;
            } else {
                trace("orderQuery " . json_encode($result), MysqlLog::ERROR);
            }
        }
        return false;
    }

    public function NotifyProcess($objData, $config, &$msg)
    {
        $msg = 'OK';
        $data = $objData->GetValues();
        if (!array_key_exists("return_code", $data)
            || (array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")
        ) {
            return false;
        }
        if (!array_key_exists("out_trade_no", $data)) {
            return false;
        }
        try {
            $checkResult = $objData->CheckSign($config);
            if ($checkResult === false) {
                return false;
            }
        } catch (Exception $e) {
            trace('检查签名 ' . json_encode($e), MysqlLog::ERROR);
        }
        //查询订单，判断订单真实性
        if (!$this->Queryorder($data["out_trade_no"])) {
            return false;
        }
        if (!array_key_exists("transaction_id", $data)) {
            return false;
        }
        return OrderOper::handle($data);
    }
}