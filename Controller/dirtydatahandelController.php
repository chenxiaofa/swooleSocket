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


    public function disconnectandnotifyAction(){
        $connList = Server::getConnections();
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        //单独删除fd-》device的redis列表
        $fdDevices = $redis->hkeys(OnlineFDToDevice);
        $fdDevices = count($fdDevices)>0?$fdDevices:[];
        $delFdDevices = array_diff($fdDevices,$connList);
        foreach ($delFdDevices as $fd){
            $device = $redis->hget(OnlineFDToDevice,$fd);
            $device = $device?unserialize($device):[];

            //判断是否存在meeting，不存在旧直接删除了
            if(!@$device['meeting_id']){
                //直接删除uuid=>fd
                $deviceFd = $redis->hget(OnlineDeviceToFd,$device['uuid']);
                $deviceFd==$fd && $redis->hdel(OnlineDeviceToFd,$device['uuid']);
                $redis->hdel(OnlineFDToDevice,$fd);
            }else{//判断会议之后删除
                $meeting = $redis->hget(OnlineMeeting,$device['meeting_id']);
                $meeting = $meeting?unserialize($meeting):null;
                if($device['dis_connect']!==0 && $device['dis_connect']<time()-2*60){//断线超过2min，直接删除掉
                        if ($meeting && $meeting['manager']==$device['uuid']){//解散会议
                            (new meetingController())->dissolveAction(['uuid'=>$device['uuid'],'meeting_id'=>$device['meeting_id']]);
                        }
                        if ($meeting && in_array($device['uuid'],array_keys($meeting['members']))){//退出会议
                            (new meetingController())->quitAction(['uuid'=>$meeting['manager'],'dis_uuid'=>$device['uuid'],'meeting_id'=>$device['meeting_id'],'is_disconnect'=>true]);
                        }
                    //直接删除uuid=>fd
                    $deviceFd = $redis->hget(OnlineDeviceToFd,$device['uuid']);
                    if ($deviceFd == $fd) $redis->hdel(OnlineDeviceToFd,$device['uuid']);

                    $redis->hdel(OnlineFDToDevice,$fd);

                }elseif($device['dis_connect']===0){//设置断线重连的时间
                    //断线通知,重连
                    if ($meeting){
                        if ($device['uuid']==$meeting['manager']){
                            $meeting['manager_info']['dis_connect'] = time();
                        }else{
                            isset($meeting['members'][$device['uuid']]) && $meeting['members'][$device['uuid']]['dis_connect'] = time();
                        }
                        //更新meeting
                        //$redis->hset(OnlineMeeting,$meeting['meeting_id'],$meeting);
                        foreach (array_merge($meeting['members'],[$meeting['manager_info']]) as $member){
                            echo "dirty data send to ".$member['uuid'];
                            Server::successSend($redis->hget(OnlineDeviceToFd,$member['uuid']),$device,FlushMeetingMembersLostConnect);
                        }
                    }
                    $device['dis_connect']=time();
                    $redis->hset(OnlineFDToDevice,$fd,serialize($device));
                }

            }
        }
        $redisHandel->put($redis);
    }

}