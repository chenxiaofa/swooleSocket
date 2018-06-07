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

    public function onlineAction($params)
    {
        if (count(array_diff(['uuid',"ip_addr",'username'],array_keys($params)))>0){
            Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError); return;
        }

        $params['created_at'] = time();
        $params['status'] = 0;
        $params['manager_uuid'] = null;
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

        //分发用户列表

        Server::successSend($GLOBALS['fd'],[]);

    }


    public function offlineAction($params){
        // fd,
        $params['fd'] = $params['fd']?:$GLOBALS['fd'];
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $device = $redis->hget(OnlineFDToDevice,$params['fd']);
        if ($device) $device = unserialize($device); else return ;
        $delFd = $redis->hget(OnlineDeviceToFd,$device['uuid']);

        if ($delFd==$params['fd']){
            $redis->hdel(OnlineDeviceToFd,$device['uuid']);
            $redis->hdel(OnlineFDToDevice,$params['fd']);
            //分发用户详细信息

        }
        $redisHandel->put($redis);

    }


    public function bindAction($params){
        if (count(array_diff(['uuid',"manager_uuid"],array_keys($params)))>0){
            Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError); return;
        }
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $deviceFd = $redis->hget(OnlineDeviceToFd,$params['uuid']);
        $device = $this->hget(OnlineFDToDevice,$deviceFd);
        $device = $device?unserialize($device):false;
        if ($device && $device['uuid'] == $params['uuid']){
            if (!$device['manager_uuid']){//不存在绑定关系，可以绑定
                $device['manager_uuid'] = $params['manager_uuid'];
                $device['status'] = time();
                $redis->hset(OnlineFDToDevice,$deviceFd,serialize($device));
                //发送给大屏通知，绑定成功

                //发送给managers，绑定成功，且发送绑定的用户信息
                return ;
            }else{
                //发送给manager通知，绑定失败，该大屏已经绑定了设备
            }
        }

        //发送给manager通知，绑定失败，不存在这个大屏设备



        $redisHandel->put($redis);
    }


    public function disbindAction($params){
        if (count(array_diff(['uuid',"manager_uuid"],array_keys($params)))>0){
            Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError); return;
        }
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $deviceFd = $redis->hget(OnlineDeviceToFd,$params['uuid']);
        $device = $this->hget(OnlineFDToDevice,$deviceFd);
        $device = $device?unserialize($device):false;
        if ($device && $device['uuid'] == $params['uuid']){
            if ($device['manager_uuid']!== $params['manager_uuid']){//不存在绑定关系，不能解绑
                $device['manager_uuid'] = null;
                $device['status'] = 0;
                $redis->hset(OnlineFDToDevice,$deviceFd,serialize($device));
                //发送给大屏通知，解除绑定成功


                //发送给managers，解除绑定成功，且发送绑定的用户信息
                return ;
            }else{
                //发送给manager通知，解除绑定失败，该大屏绑定的不是这台设备
            }
        }

        //发送给manager通知，绑定失败，不存在这个大屏设备



        $redisHandel->put($redis);
    }

//    public function offlineAction($params){
//        if (count(array_diff(['uuid'],array_keys($params)))>0){
//            Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError); return;
//        }
//        $redisHandel = Redis::getInstance();
//        $redis = $redisHandel->get();
//        $deviceFd = $redis->hget(OnlineDeviceToFd,$params['uuid']);
//        $device = $redis->hget(OnlineFDToDevice,$deviceFd);
//        $device = $device?unserialize($device):[];
//        if ($device && $device['uuid'] == $params['uuid']){
//            $redis->hdel(OnlineFDToDevice,$deviceFd);
//            $redis->hdel(OnlineDeviceToFd,$params['uuid']);
//
//            //发送用户退出的详细信息
//        }
//
//    }
//



}