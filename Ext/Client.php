<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-6-8
 * Time: 上午10:50
 */

namespace Ext;


class Client
{
    public static function send(array $data){
        $clientHandel = \core\Client::getInstance();
        return $clientHandel->send(json_encode($data));
    }
}