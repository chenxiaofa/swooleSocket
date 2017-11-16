<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/9
 * Time: 18:30
 */
namespace Controller;

use core\Redis;
use Model\testModel;

class indexController
{

    public function indexAction($params)
    {
        echo "常规默认选项";
    }
}