<?php

namespace util;

abstract class TokenOper
{
    protected $expire_time = 0;
    protected $value = '';

    protected $cache_key = '';
    protected $api = '';
    protected $sk = '';

    private $action_time = 305; // seconds

    function __construct($cache_key, $api, $sk)
    {
        $this->cache_key = "TokenOper$cache_key";
        $this->api = $api;
        $this->sk = $sk;
        if ("?{$this->cache_key}") {
            $db = cache($this->cache_key);
            $db = json_decode($db, true);
            if (isset($db['value'])) {
                $this->value = $db['value'];
            }
            if (isset($db['expire_time'])) {
                $this->expire_time = intval($db['expire_time']);
            }
        }
    }

    abstract protected function updateValue();

    public function get()
    {
        $duration = $this->expire_time - time();
        if ($duration <= 0) {
            $this->updateValue();
            trace("Refresh On Get [$duration] {$this->cache_key} {$this->value}", MysqlLog::ERROR);
            $duration = $this->expire_time - time();
        }
        trace("Get [$duration] {$this->cache_key} {$this->value}", MysqlLog::DEBUG);
        return $this->value;
    }

    public function refresh()
    {
        $duration = $this->expire_time - time();
        if ($duration <= $this->action_time) {
            $this->updateValue();
            trace("Refresh [$duration] {$this->cache_key} {$this->value}", MysqlLog::LOG);
        }
    }
}
