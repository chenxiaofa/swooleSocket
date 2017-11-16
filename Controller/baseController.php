<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-11-15
 * Time: 上午11:21
 */

namespace Controller;


use core\Redis;

class baseController
{
    public $redis = null;

    public function __construct()
    {
        $redis = Redis::getInstance();
        $redis->redis();
    }
}