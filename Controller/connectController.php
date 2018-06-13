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
    public function reconnectAction($params)
    {
        if (count(array_diff(['uuid','ip_addr'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
            return;
        }
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $oldFd = $redis->hget(OnlineDeviceToFd, $params['uuid']);
        $deviceInfo = $redis->hget(OnlineFDToDevice, $oldFd);
        if ($oldFd && $deviceInfo) {//重连信息依然存在
            $deviceInfo = unserialize($deviceInfo);
            $deviceInfo['dis_connect'] = 0;
            $redis->hset(OnlineDeviceToFd, $params['uuid'], $GLOBALS['fd']);
            $redis->hdel(OnlineFDToDevice, $oldFd);
            $redis->hset(OnlineFDToDevice, $GLOBALS['fd'], serialize($deviceInfo));
            $meeting = $redis->hget(OnlineMeeting, $deviceInfo['meeting_id']);
            $meeting = $meeting ? unserialize($meeting) : [];
            if ($meeting && isset($meeting['members'][$deviceInfo['uuid']])) {
                //重连成功，通知
                $meeting['members'][$deviceInfo['uuid']] = $deviceInfo;
                $redis->hset(OnlineMeeting, $deviceInfo['meeting_id'], serialize($meeting));
                foreach (array_merge($meeting['members'], [$meeting['manager_info']]) as $member) {
                    if ($member['uuid'] == $deviceInfo['uuid']) {
                        Server::successSend($redis->hget(OnlineDeviceToFd, $member['uuid']), $deviceInfo, ReconnectSuccess);
                    } else {
                        Server::successSend($redis->hget(OnlineDeviceToFd, $member['uuid']), $deviceInfo, FlushMeetingMembersReConnect);
                    }
                }
            }
            //重连要更新一下通知
            //Server::successSend($GLOBALS['fd'],$meeting,FlushMeetingMembersSuccess);
        } else {//重连信息不存在，返回失败

            $params['created_at'] = time();
            $params['meeting_id'] = null;
            $params['username'] = null;
            $params['dis_connect'] = 0;
            $redis->hset(OnlineFDToDevice,$GLOBALS['fd'],serialize($params));
            $redis->hset(OnlineDeviceToFd,$params['uuid'],$GLOBALS['fd']);
            Server::successSend($GLOBALS['fd'], $params, ReconnectSuccess);
            //Server::failedSend($GLOBALS['fd'], [], ReconnectFailed);
        }
        $redisHandel->put($redis);
    }

    // 正常断开，删除fd，存在会议的，需要先退出会议
    public function disconnectAction($params)
    {
        // fd,
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $device = $redis->hget(OnlineFDToDevice, $params['fd']);
        if ($device) $device = unserialize($device); else return;
        $delFd = $redis->hget(OnlineDeviceToFd, $device['uuid']);

        $meeting = $redis->hget(OnlineMeeting, $device['meeting_id']);
        $meeting = $meeting ? unserialize($meeting) : null;

        if ($delFd == $params['fd']) {
            //判断是否存在会议，如果存在会议，则给定一个异常断开的状态
            if (!$meeting) {
                $redis->hdel(OnlineDeviceToFd, $device['uuid']);
                $redis->hdel(OnlineFDToDevice, $params['fd']);
            } else {
                //还需要做一个断线的通知
                //断线通知,重连
                $device['dis_connect'] = time();

                if ($device['uuid'] == $meeting['manager']) {
                    $meeting['manager_info'] = $device;
                } else {
                    if (isset($meeting['members'][$device['uuid']])) $meeting['members'][$device['uuid']] = $device;
                }
                //更新meeting
                //$redis->hset(OnlineMeeting,$meeting['meeting_id'],$meeting);
                foreach (array_merge($meeting['members'], [$meeting['manager_info']]) as $member) {
                    echo "disconnect send to " . $member['uuid'] . "\n";
                    Server::successSend($redis->hget(OnlineDeviceToFd, $member['uuid']), $device, FlushMeetingMembersLostConnect);
                }

                $redis->hset(OnlineFDToDevice, $delFd, serialize($device));
            }
        }

        $redisHandel->put($redis);

    }


}