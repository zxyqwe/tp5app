<?php

namespace wxsdk\pay;

/**
 *
 * 提交支付输入对象
 * @author zxyqwe
 *
 */
class WxPayTransfer extends WxPayDataBase
{
    /**
     * 设置微信分配的公众账号ID
     * @param string $value
     **/
    public function SetAppid($value)
    {
        $this->values['mch_appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     **/
    public function GetAppid()
    {
        return $this->values['mch_appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     **/
    public function IsAppidSet()
    {
        return array_key_exists('mch_appid', $this->values);
    }


    /**
     * 设置微信支付分配的商户号
     * @param string $value
     **/
    public function SetMch_id($value)
    {
        $this->values['mchid'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     **/
    public function GetMch_id()
    {
        return $this->values['mchid'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     **/
    public function IsMch_idSet()
    {
        return array_key_exists('mchid', $this->values);
    }


    /**
     * 设置微信支付分配的终端设备号，与下单一致
     * @param string $value
     **/
    public function SetDevice_info($value)
    {
        $this->values['device_info'] = $value;
    }

    /**
     * 获取微信支付分配的终端设备号，与下单一致的值
     * @return 值
     **/
    public function GetDevice_info()
    {
        return $this->values['device_info'];
    }

    /**
     * 判断微信支付分配的终端设备号，与下单一致是否存在
     * @return true 或 false
     **/
    public function IsDevice_infoSet()
    {
        return array_key_exists('device_info', $this->values);
    }


    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value
     **/
    public function SetNonce_str($value)
    {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     **/
    public function GetNonce_str()
    {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     **/
    public function IsNonce_strSet()
    {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置微信openid
     * @param string $value
     **/
    public function SetOpen_id($value)
    {
        $this->values['openid'] = $value;
    }

    /**
     * 获取微信openid的值
     * @return 值
     **/
    public function GetOpen_id()
    {
        return $this->values['openid'];
    }

    /**
     * 判断微信openid是否存在
     * @return true 或 false
     **/
    public function IsOpen_idSet()
    {
        return array_key_exists('openid', $this->values);
    }


    /**
     * 设置商户系统内部的订单号
     * @param string $value
     **/
    public function SetOut_trade_no($value)
    {
        $this->values['partner_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号
     * @return 值
     **/
    public function GetOut_trade_no()
    {
        return $this->values['partner_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号
     * @return true 或 false
     **/
    public function IsOut_trade_noSet()
    {
        return array_key_exists('partner_trade_no', $this->values);
    }


    /**
     * 设置check_name
     * @param string $value
     **/
    public function SetCheck_name($value)
    {
        $this->values['check_name'] = $value;
    }

    /**
     * 获取check_name
     * @return 值
     **/
    public function GetCheck_name()
    {
        return $this->values['check_name'];
    }

    /**
     * 判断check_name
     * @return true 或 false
     **/
    public function IsCheck_nameSet()
    {
        return array_key_exists('check_name', $this->values);
    }


    /**
     * 设置订单总金额，单位为分，只能为整数，详见支付金额
     * @param string $value
     **/
    public function SetTotal_fee($value)
    {
        $this->values['amount'] = $value;
    }

    /**
     * 获取订单总金额，单位为分，只能为整数，详见支付金额的值
     * @return 值
     **/
    public function GetTotal_fee()
    {
        return $this->values['amount'];
    }

    /**
     * 判断订单总金额，单位为分，只能为整数，详见支付金额是否存在
     * @return true 或 false
     **/
    public function IsTotal_feeSet()
    {
        return array_key_exists('amount', $this->values);
    }


    /**
     * 设置re_user_name
     * @param string $value
     **/
    public function SetUser_name($value)
    {
        $this->values['re_user_name'] = $value;
    }

    /**
     * 获取re_user_name
     * @return 值
     **/
    public function GetUser_name()
    {
        return $this->values['re_user_name'];
    }

    /**
     * 判断re_user_name
     * @return true 或 false
     **/
    public function IsUser_nameSet()
    {
        return array_key_exists('re_user_name', $this->values);
    }


    /**
     * 设置Desc
     * @param string $value
     **/
    public function SetDesc($value)
    {
        $this->values['desc'] = $value;
    }

    /**
     * 获取Desc
     * @return 值
     **/
    public function GetDesc()
    {
        return $this->values['desc'];
    }

    /**
     * 判断Desc
     * @return true 或 false
     **/
    public function IsDescSet()
    {
        return array_key_exists('desc', $this->values);
    }


    /**
     * 设置APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
     * @param string $value
     **/
    public function SetSpbill_create_ip($value)
    {
        $this->values['spbill_create_ip'] = $value;
    }

    /**
     * 获取APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。的值
     * @return 值
     **/
    public function GetSpbill_create_ip()
    {
        return $this->values['spbill_create_ip'];
    }

    /**
     * 判断APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。是否存在
     * @return true 或 false
     **/
    public function IsSpbill_create_ipSet()
    {
        return array_key_exists('spbill_create_ip', $this->values);
    }
}
