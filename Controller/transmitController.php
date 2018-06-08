<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-6-8
 * Time: 上午11:41
 */

namespace Controller;


use core\Redis;
use Ext\Client;
use Ext\Server;

class transmitController
{
    public function signalAction($params)
    {
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        Server::successSend($redis->hget(OnlineDeviceToFd,$params['manager_uuid']),$params['data'],$params['code']);
        $redisHandel->put($redis);
    }


    public function broadcastAction($params){
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $meetings = $redis->hgetall(OnlineMeeting);
        foreach ($meetings as $meeting){
            $meeting = unserialize($meeting);
            Server::successSend($redis->hget(OnlineDeviceToFd,$meeting['manager']),$params['data'],$params['code']);
        }

        $redisHandel->put($redis);
    }


    public function requestAction($params){
        var_dump($params);
        if (count(array_diff(['screen_uuid', 'manager_uuid'], array_keys($params['params']))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);return;
        }
        Client::send($params);
    }

}