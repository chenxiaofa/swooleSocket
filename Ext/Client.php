<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 2018/6/7
 * Time: 10:07
 */

namespace Ext;


class Client
{
    public static function send(array $data){
        $clientHandel = \core\Client::getInstance();
        echo "sned Data:".json_encode($data)."\n";
        return $clientHandel->send(json_encode($data));
    }
}