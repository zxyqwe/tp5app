<?php

namespace util\stat;


abstract class BaseStat
{
    abstract public function generateOneDay();

    abstract public function OutputAll();
}