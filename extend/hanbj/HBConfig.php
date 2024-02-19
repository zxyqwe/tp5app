<?php

namespace hanbj;

class HBConfig
{
    // 维护者：神棍
    const CODER = '坎丙午';
    // 内网用户：会长层 + 一些人
    const FIXED = [self::CODER, '坤丁酉', '乾壬申', '乾乙丑', '巽戊午', '兑庚午', '夏庚子','乾丙子','乾戊辰','兑甲辰'];
    // 活动扫码工作人员：会长层 + 部长层 + 一些人
    const WORKER = [self::CODER, '乾壬申'];
    // 本届吧务组年代
    const YEAR = 18;
    // 收个开放号码ID
    const FIRST_UNAME_ID = 863;
}
