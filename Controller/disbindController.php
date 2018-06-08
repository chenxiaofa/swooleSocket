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

class disbindController
{
    public function request($params)
    {
        if (count(array_diff(['manager_info', 'uuid'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
            return;
        }
        Client::send($params);
    }


    public function success($params)
    {
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $meetings = $redis->hgetall(OnlineMeeting);
        foreach ($meetings as $meeting) {
            $meeting = unserialize($meeting);
            Server::successSend($redis->hget(OnlineDeviceToFd, $meeting['manager']), $params, ScreenBindSuccess);
        }

        $redisHandel->put($redis);
    }

    public function fail($params)
    {
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        switch ($params['code']) {
            case 'missmatch':
                Server::failedSend($redis->hget(OnlineDeviceToFd, $params['manager_uuid']), [], ScreenDisBindMissMatch);
            case 'miss':
                Server::failedSend($redis->hget(OnlineDeviceToFd, $params['manager_uuid']), [], ScreenDisBindMiss);
            default:
                echo "invalid code", $params['code'], "\n";
        }

        $redisHandel->put($redis);
    }
}