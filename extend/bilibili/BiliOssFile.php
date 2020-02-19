<?php

namespace bilibili;

use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class BiliOssFile
{
    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public static function getAllData()
    {
        $ret = Db::table("bilioss")
            ->field([
                'av',
                'length as l',
                'pubdate as p',
                'title as t'
            ])
            ->select();
        $kvmap = [];
        foreach ($ret as $item) {
            $kvmap[$item['av']] = $item;
        }
        return $kvmap;
    }
}

/*
 CREATE TABLE `bilioss` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `av` varchar(255) NOT NULL,
  `length` varchar(45) DEFAULT NULL,
  `pubdate` varchar(45) DEFAULT NULL,
  `title` varchar(2048) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `av_name` (`av`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */