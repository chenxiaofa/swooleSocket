<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-6-7
 * Time: 上午11:15
 */

namespace Controller;


use core\Redis;
use Ext\Client;
use Ext\Server;

class bindController
{

        public function requestAction($params){
            if (count(array_diff(['uuid', 'manager_uuid','manager_info'], array_keys($params))) > 0) {
                Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);return;
            }
            Client::send($params);
        }
}