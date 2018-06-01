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
                Server::failedSend($GLOBALS['fd'],[],ParamsRequiredError);
            }
            $redisHandel = Redis::getInstance();
            $redis = $redisHandel->get();
            $oldFd = $redis->hget(OnlineDeviceToFd,$params['uuid']);
            $deviceInfo = $redis->hget(OnlineFDToDevice,$oldFd);
            if ($oldFd && $deviceInfo){//重连信息依然存在
                $deviceInfo = unserialize($deviceInfo);
                $deviceInfo['dis_connect']=0;
                $redis->hset(OnlineDeviceToFd,$params['uuid'],$GLOBALS['fd']);
                $redis->hset(OnlineFDToDevice,$GLOBALS['fd'],$deviceInfo);
                $meeting = $redis->hget(OnlineMeeting,$deviceInfo['meeting_id']);
                $meeting = $meeting?unserialize($meeting):[];
                if ($meeting){
                    //重连成功，通知
                    foreach (array_push($meeting['members'],$meeting['manager_info']) as $member){
                        Server::successSend($redis->hget(OnlineDeviceToFd,$member['uuid']),$meeting,ReconnectSuccess);
                    }
                }
            //重连要更新一下通知
            //Server::successSend($GLOBALS['fd'],$meeting,FlushMeetingMembersSuccess);
            }else{//重连信息不存在，返回失败
                Server::failedSend($GLOBALS['fd'],[],ReconnectFailed);
            }
            $redisHandel->put($redis);
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
                //判断是否存在会议，如果存在会议，则给定一个异常断开的状态
                if(!@$device['meeting_id']){
                    $redis->hdel(OnlineDeviceToFd,$device['uuid']);
                    $redis->hdel(OnlineFDToDevice,$params['fd']);
                }else{
                    //还需要做一个断线的通知
                    //断线通知,重连
                    $meeting = $redis->hget(OnlineMeeting,$device['meeting_id']);
                    $meeting = $meeting?unserialize($meeting):null;
                    if ($meeting){
                        if ($device['uuid']==$meeting['manager']){
                            $meeting['manager_info']['dis_connect'] = time();
                        }else{
                            isset($meeting['members'][$device['uuid']]) && $meeting['members'][$device['uuid']]['dis_connect'] = time();
                        }
                        //更新meeting
                        //$redis->hset(OnlineMeeting,$meeting['meeting_id'],$meeting);

                        foreach (array_push($meeting['members'],$meeting['manager_info']) as $member){
                            echo "disconnect send to ".$member['uuid'];
                            Server::successSend($redis->hget(OnlineDeviceToFd,$member['uuid']),$meeting,ConnectLosted);
                        }

                    }
                    $device['dis_connect']=time();
                    $redis->hset(OnlineFDToDevice,$delFd,serialize($device));
                }
            }

            $redisHandel->put($redis);

        }


}