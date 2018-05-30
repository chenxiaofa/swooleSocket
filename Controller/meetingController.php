<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-5-8
 * Time: 上午9:31
 */

namespace Controller;


use core\Redis;
use Ext\Server;

class meetingController
{
    /**
     * @param $params
     * 创建会议
     */
    public function createAction($params)
    {

        if (count(array_diff(['name', 'monitor', 'uuid'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
        }

        $meeting_id = $this->createMeetingId();

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();



        $managerInfo = $redis->hget(OnlineFDToDevice, $GLOBALS['fd']);
        $managerInfo = unserialize($managerInfo);
        $managerInfo['meeting_id'] = $params['meeting_id'];

        $meeting = [
            'meeting_id' => $meeting_id,
            'name' => $params['name'],
            'manager' => $params['uuid'],
            'manager_info'=> $managerInfo,
            'monitor'=>$params['monitor'],
            'create_time'=>time(),
            'members' => []
        ];

        $redis->hset(OnlineMeeting, $meeting_id, serialize($meeting));
        $redis->hset(OnlineFDToDevice, $GLOBALS['fd'], serialize($managerInfo));


        $redisHandel->put($redis);
        Server::successSend($GLOBALS['fd'], ['meeting_id'=>$meeting_id], MeetingCreateSuccess);
    }


    private function createMeetingId(){
        $meeting_id = mt_rand(100000,999999);
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        if($redis->hexists(OnlineMeeting,$meeting_id)){
            return $this->createMeetingId();
        }
        $redisHandel->put($redis);
        return $meeting_id;
    }

    /**
     * @param $params
     * 加入会议
     */
    public function joinAction($params)
    {
        if (count(array_diff(['meeting_id', 'uuid'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
        }

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $meeting = $redis->hget(OnlineMeeting, $params['meeting_id']);

        if ($meeting) {//会议记录是否存在
            $meeting = unserialize($meeting);

            $member = $redis->hget(OnlineFDToDevice, $GLOBALS['fd']);
            $member = unserialize($member);
            $member['meeting_id'] = $params['meeting_id'];
            $redis->hset(OnlineFDToDevice, $GLOBALS['fd'], serialize($member));
            $meeting['members'][$params['uuid']] = $member;

            $redis->hset(OnlineMeeting, $params['meeting_id'], serialize($meeting));
            Server::successSend($redis->hget(OnlineDeviceToFd, $meeting['manager']), $meeting, FlushMeetingMembersSuccess);//发送通知给主持人
            foreach ($meeting['members'] as $uuid => $info) {
                Server::successSend($redis->hget(OnlineDeviceToFd, $uuid), $meeting, FlushMeetingMembersSuccess);//发送通知给专家
            }

        } else {
            Server::failedSend($GLOBALS['fd'], [], NoMeetingError);
        }

        $redisHandel->put($redis);

    }

    /**
     * @param $params
     * 消息转发，包含（请求主持人退出会议，专家结语，）
     */
    public function repeatAction($params)
    {
        if (count(array_diff(['meeting_id', 'uuid', 'to_uuid', 'message'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
        }
        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $meeting = $redis->hget(OnlineMeeting, $params['meeting_id']);
        if ($meeting) {
            $meeting = unserialize($meeting);
            if (($params['uuid'] == $meeting['manager'] || in_array($params['uuid'], array_keys($meeting['members']))) && ($params['to_uuid'] == $meeting['manager'] || in_array($params['to_uuid'], array_keys($meeting['members'])))) {
                Server::successSend($redis->hget(OnlineDeviceToFd, $params['to_uuid']), $params, RepeatMessageSuccess);
            }
        }

        $redisHandel->put($redis);
    }

    /**
     * @param $params
     * 退出
     */
    public function quitAction($params)
    {
        if (count(array_diff(['meeting_id', 'uuid', 'dis_uuid'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
        }

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $meeting = $redis->hget(OnlineMeeting, $params['meeting_id']);
        if ($meeting) {
            $meeting = unserialize($meeting);
            if ($meeting['manager'] == $params['uuid']) {
                //$disFd = array_search($params['dis_uuid'],$meeting['members']);
                $disMember = @$meeting['members'][$params['dis_uuid']];
                if ($disMember) {
                    $disMember['meeting_id'] = null;
                    $disFd = $redis->hget(OnlineDeviceToFd, $params['dis_uuid']);
                    $redis->hset(OnlineFDToDevice, $disFd, serialize($disMember));
                    unset($meeting['members'][$params['dis_uuid']]);
                    $redis->hset(OnlineMeeting, $params['meeting_id'], serialize($meeting));
                    foreach ($meeting['members'] as $uuid => $info) {
                        Server::successSend($redis->hget(OnlineDeviceToFd, $uuid), $meeting, FlushMeetingMembersSuccess);
                    }
                    Server::successSend($disFd, [], QuitMeetingSuccess);

                    if (isset($params['is_disconnect']) && $params['is_disconnect'] == true) {//兼容断线
                        Server::successSend($redis->hget(OnlineDeviceToFd, $meeting['manager']), $meeting, PromiseQuitMeetingSuccess);
                    } else {
                        Server::successSend($GLOBALS['fd'], $meeting, PromiseQuitMeetingSuccess);
                    }

                }

            } else {
                Server::failedSend($GLOBALS['fd'], [], NotManagerError);
            }
        } else {
            Server::failedSend($GLOBALS['fd'], [], NoMeetingError);
        }

        $redisHandel->put($redis);


    }


    public function dissolveAction($params)
    {
        if (count(array_diff(['meeting_id', 'uuid'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);
        }

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();
        $meeting = $redis->hget(OnlineMeeting, $params['meeting_id']);
        if ($meeting) {
            $meeting = unserialize($meeting);
            foreach ($meeting['members'] as $uuid => $info) {
                $info['meeting_id'] = null;
                $fd = $redis->hget(OnlineDeviceToFd, $uuid);
                $redis->hset(OnlineFDToDevice, $fd, serialize($info));
                Server::successSend($fd, [], DissolveMeetingSuccess);
            }

            $managerFd = $redis->hget(OnlineDeviceToFd, $meeting['manager']);
            $managerInfo = unserialize($redis->hget(OnlineFDToDevice, $managerFd));
            $managerInfo['meeting_id'] = null;
            $redis->hset(OnlineFDToDevice, $managerFd, serialize($managerInfo));
            Server::successSend($redis->hget(OnlineDeviceToFd, $meeting['manager']), [], DissolveMeetingSuccess);
        }

        $redis->del(OnlineMeeting, $params['meeting_id']);

        $redisHandel->put($redis);
    }


}