<?php

namespace util\stat;


abstract class BaseStat
{
    /**
     * @return false|array(string, string)
     */
    abstract public function generateOneDay();

    abstract public function OutputAll();
}