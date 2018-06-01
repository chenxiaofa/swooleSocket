<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/9
 * Time: 18:30
 */
namespace Controller;

use core\Redis;
use Ext\Server;
use Model\testModel;

class indexController
{
    public $params = null;
    //fd 和 uuid 各自进行删除

    public function initAction($params)
    {
        if (count(array_diff(['uuid',"ip_addr"],array_keys($params)))>0){
            Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError);
        }

        $params['created_at'] = time();
        $params['meeting_id'] = null;
        $params['username'] = null;
        $params['dis_connect'] = 0;

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $oldDeviceFd = $redis->hget(OnlineDeviceToFd,$params['uuid']);

        $redis->multi();
        if($oldDeviceFd){//如果存在旧设备，则删除旧的fd基础信息
            $redis->hdel(OnlineFDToDevice,$oldDeviceFd);
        }
        $redis->hset(OnlineFDToDevice,$GLOBALS['fd'],serialize($params));
        $redis->hset(OnlineDeviceToFd,$params['uuid'],$GLOBALS['fd']);
        $redis->exec();

        $redisHandel->put($redis);

        Server::successSend($GLOBALS['fd'],[]);
        //$GLOBALS['serv']->send($GLOBALS['fd'],"已经绑定初始化成功\r\n");
        //var_dump($params);
    }

}