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

class dirtydatahandelController
{

    public function disconnectandnotifyAction(){
        $connList = Server::getConnections();
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        //单独删除fd-》device的redis列表
        $fdDevices = $redis->hkeys(OnlineFDToDevice);
        $fdDevices = $fdDevices?:[];
        $delFdDevices = array_diff($fdDevices,$connList);
        foreach ($delFdDevices as $fd){
            $device = $redis->hget(OnlineFDToDevice,$fd);
            $device = $device?unserialize($device):[];
            if($device){
                (new dirtydatahandelController())->offlineAction(['uuid'=>$device['uuid']]);
            }
        }
        $redisHandel->put($redis);
    }

}