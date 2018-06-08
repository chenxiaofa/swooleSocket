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
            Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError); return;
        }

        $params['created_at'] = time();
        $params['meeting_id'] = null;
        $params['username'] = null;
        $params['dis_connect'] = 0;

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $oldDeviceFd = $redis->hget(OnlineDeviceToFd,$params['uuid']);
        var_dump($oldDeviceFd); echo "<====oldDeviceFd:\n";
        $redis->multi();
        if($oldDeviceFd){//如果存在旧设备，则删除旧的fd基础信息
            //如果存在olddevice，則需要先退出會議
            $oldDevice = $redis->hget(OnlineFDToDevice,$oldDeviceFd);
            $oldDevice = $oldDevice?unserialize($oldDevice):[];
            echo "oldDevice:\n";var_dump($oldDevice);
            if (@$oldDevice['meeting_id']){
                $meeting = $redis->hget(OnlineMeeting,$oldDevice['meeting_id']);
                $meeting && $meeting = unserialize($meeting);

                if ($meeting && $meeting['manager']==$oldDevice['uuid']){//解散会议
                    (new meetingController())->dissolveAction(['uuid'=>$oldDevice['uuid'],'meeting_id'=>$oldDevice['meeting_id']]);
                }
                if ($meeting && in_array($oldDevice['uuid'],array_keys($meeting['members']))){//退出会议
                    (new meetingController())->quitAction(['uuid'=>$meeting['manager'],'dis_uuid'=>$oldDevice['uuid'],'meeting_id'=>$oldDevice['meeting_id'],'is_disconnect'=>true]);
                }

            }

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