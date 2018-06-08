<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/9
 * Time: 18:30
 */
namespace Controller;

use core\Redis;
use Ext\Client;
use Ext\Server;
use Model\testModel;

class indexController
{
    public $params = null;

    //fd 和 screen_uuid 各自进行删除

    public function onlineAction($params)
    {
        if (count(array_diff(['screen_uuid', "ip_addr", 'username'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
            return;
        }

        $params['created_at'] = time();
        $params['status'] = 0;
        $params['manager_uuid'] = null;
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $oldDeviceFd = $redis->hget(OnlineDeviceToFd, $params['screen_uuid']);
        $redis->multi();
        if ($oldDeviceFd) {//如果存在旧设备，则删除旧的fd基础信息
            $redis->hdel(OnlineFDToDevice, $oldDeviceFd);
        }
        $redis->hset(OnlineFDToDevice, $GLOBALS['fd'], serialize($params));
        $redis->hset(OnlineDeviceToFd, $params['screen_uuid'], $GLOBALS['fd']);
        $redis->exec();


        //分发用户信息
        Client::send(['path'=>'transmit/broadcast','params'=>['data'=>$params,'code'=>OnlineSuccessForManager]]);
        Server::successSend($GLOBALS['fd'], [],OnlineSuccess);
        $redisHandel->put($redis);
    }


    public function offlineAction($params)
    {
        // fd,
        $params['fd'] = $params['fd'] ?: $GLOBALS['fd'];
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $device = $redis->hget(OnlineFDToDevice, $params['fd']);
        if ($device) $device = unserialize($device); else return;
        $delFd = $redis->hget(OnlineDeviceToFd, $device['screen_uuid']);

        if ($delFd == $params['fd']) {
            $redis->hdel(OnlineDeviceToFd, $device['screen_uuid']);
            $redis->hdel(OnlineFDToDevice, $params['fd']);
            //分发用户详细信息
            Client::send(['path'=>'transmit/broadcast','params'=>['data'=>$params,'code'=>OfflineSuccessForManager]]);
            //Server::successSend($params['fd'], [],OfflineSuccess);
        }
        $redisHandel->put($redis);

    }


    public function bindAction($params)
    {
        if (count(array_diff(['screen_uuid', "manager_uuid",'manager_info'], array_keys($params))) > 0) {
            //发送给manager通知，
            //  Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError); return;
        }
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $deviceFd = $redis->hget(OnlineDeviceToFd, $params['screen_uuid']);
        $device = $redis->hget(OnlineFDToDevice, $deviceFd);
        $device = $device ? unserialize($device) : false;
        if ($device && $device['screen_uuid'] == $params['screen_uuid']) {
            if (!$device['manager_uuid']) {//不存在绑定关系，可以绑定
                $device['manager_uuid'] = $params['manager_uuid'];
                $device['manager_info'] = $params['manager_info'];
                $device['status'] = time();
                $redis->hset(OnlineFDToDevice, $deviceFd, serialize($device));
                //发送给大屏通知，绑定成功
                Server::successSend($deviceFd,$params['manager_info'],BindSuccess);

                //发送给managers，绑定成功，且发送绑定的大屏信息
                Client::send(['path'=>'transmit/broadcast','params'=>['data'=>$device,'code'=>BindSuccessForManager]]);
                $redisHandel->put($redis);
                return;
            } else {
                //发送给manager通知，绑定失败，该大屏已经绑定了设备
                Client::send(['path'=>'transmit/signal','params'=>['manager_uuid'=>$params['manager_uuid'],'data'=>[],'code'=>BindFailRepeatForManager]]);
                $redisHandel->put($redis);
                return;
            }
        }

        //发送给manager通知，绑定失败，不存在这个大屏设备
        Client::send(['path'=>'transmit/signal','params'=>['manager_uuid'=>$params['manager_uuid'],'data'=>[],'code'=>BindFailMisForManager]]);

        $redisHandel->put($redis);
    }


    public function disbindAction($params)
    {
        if (count(array_diff(['screen_uuid', "manager_uuid"], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
            return;
        }
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $deviceFd = $redis->hget(OnlineDeviceToFd, $params['screen_uuid']);
        $device = $this->hget(OnlineFDToDevice, $deviceFd);
        $device = $device ? unserialize($device) : false;
        if ($device && $device['screen_uuid'] == $params['screen_uuid']) {
            if ($device['manager_uuid'] !== $params['manager_uuid']) {//不存在绑定关系，不能解绑
                $device['manager_uuid'] = null;
                $device['status'] = 0;
                $redis->hset(OnlineFDToDevice, $deviceFd, serialize($device));
                //发送给大屏通知，解除绑定成功
                Server::successSend($deviceFd,$params,DisBindSuccess);

                //发送给managers，解除绑定成功，且发送绑定的用户信息
                Client::send(['path'=>'transmit/broadcast','params'=>['code'=>DisBindSuccessForManager,'data'=>$device]]);
                $redisHandel->put($redis);
                return;
            } else {
                //发送给manager通知，解除绑定失败，该大屏绑定的不是这台设备
                Client::send(['path'=>'transmit/signal','params'=>['manager_uuid'=>$params['screen_uuid'],'code'=>DisBindFailMismatchForManager,'data'=>[],]]);
                $redisHandel->put($redis);
                return;
            }
        }

        //发送给manager通知，绑定失败，不存在这个大屏设备
        Client::send(['path'=>'transmit/signal','params'=>['manager_uuid'=>$params['screen_uuid'],'code'=>DisBindFailMissForManager,'data'=>[]]]);

        $redisHandel->put($redis);
    }


}