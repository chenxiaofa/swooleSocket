<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-5-8
 * Time: 下午2:54
 */

namespace Controller;


use core\Redis;
use Ext\Server;

class connectController
{
        //异常断开，重新链接，替换fd即可
        public function reconnectAction($params){
            if (count(array_diff(['uuid'],array_keys($params)))>0){
                Server::failedSend($GLOBALS['fd'],[],'params is required');
            }
            $redisHandel = Redis::getInstance();
            $redis = $redisHandel->get();
            $oldFd = $redis->hget(OnlineDeviceToFd,$params['uuid']);
            $deviceInfo = $redis->hget(OnlineFDToDevice,$oldFd);
            $redis->hset(OnlineDeviceToFd,$params['uuid'],$GLOBALS['fd']);
            $redis->hset(OnlineFDToDevice,$GLOBALS['fd'],$deviceInfo);
            $redisHandel->put($redis);
            $meeting = $redis->hget(OnlineMeeting,$deviceInfo['meeting_id']);
            $meeting = $meeting?unserialize($meeting):[];
            Server::successSend($GLOBALS['fd'],$meeting);
        }

        // 正常断开，删除fd，存在会议的，需要先退出会议
        public function disconnectAction($params){
            // fd,
            $redisHandel = Redis::getInstance();
            $redis = $redisHandel->get();
            $device = $redis->hget(OnlineFDToDevice,$params['fd']);
            if ($device) $device = unserialize($device);
            $delFd = $redis->hget(OnlineDeviceToFd,$device['uuid']);
            if ($delFd==$params['fd']){
                if (@$device['meeting_id']){//退出会议，解散会议
                    $meeting = $redis->hget(OnlineMeeting,$device['meeting_id']);
                    $meeting = $meeting?unserialize($meeting):null;
                    if ($meeting && $meeting['manager']==$device['uuid']){//解散会议
                        (new meetingController())->dissolveAction(['uuid'=>$device['uuid'],'meeting_id'=>$device['meeting_id']]);
                    }
                    if ($meeting && in_array($device['uuid'],array_keys($meeting['members']))){//退出会议
                        (new meetingController())->quitAction(['uuid'=>$meeting['manager'],'dis_uuid'=>$device['uuid'],'meeting_id'=>$device['meeting_id']]);
                    }


                }

                $redis->hdel(OnlineDeviceToFd,$device['uuid']);
            }
            $redis->hdel(OnlineFDToDevice,$params['fd']);
            $redisHandel->put($redis);

        }


}