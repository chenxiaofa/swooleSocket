<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-5-9
 * Time: 上午11:56
 */

namespace Controller;


use core\Redis;
use Ext\Server;

class dirtyDataHandelController
{
    public function timingDelAction()
    {//每天删除一次脏数据
        $connList = Server::getConnections();
        $connList = count($connList)>0?$connList:[];
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        //单独删除fd-》device的redis列表
        $fdDevices = $redis->hkeys(OnlineFDToDevice);
        $fdDevices = count($fdDevices)>0?$fdDevices:[];
        $delFdDevices = array_diff($fdDevices,$connList);
        foreach ($delFdDevices as $value){
            $redis->hdel(OnlineFDToDevice,$value);
        }
        //单独删除device-》fd的redis列表
        $deviceFd = $redis->hgetall(OnlineDeviceToFd);
        $deviceFd = array_flip($deviceFd);
        $fds = count($deviceFd)>0?array_keys($deviceFd):[];
        $delDeviceFd = array_diff($fds,$connList);
        foreach ($delDeviceFd as $value){
            $redis->hdel(OnlineDeviceToFd,$deviceFd[$value]);
        }

        $redisHandel->put($redis);
    }
}