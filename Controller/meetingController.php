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

        if (count(array_diff(['name', 'monitor', 'uuid','username'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);return;
        }

        $meeting_id = $this->createMeetingId();

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $managerInfo = $redis->hget(OnlineFDToDevice, $GLOBALS['fd']);
        $managerInfo = unserialize($managerInfo);
        if ($managerInfo['meeting_id']){//存在meeting_id,则解散之前参加的会议或者主持的会议
            $meeting = $redis->hget(OnlineMeeting,$managerInfo['meeting_id']);
            $meeting = $meeting?unserialize($meeting):[];
            if ($meeting){
                if ($meeting['manager']==$managerInfo['uuid']){
                    $this->dissolveAction(['meeting_id'=>$managerInfo['meeting_id'],'uuid'=>$managerInfo['uuid']]);
                }else{
                    $this->quitAction(['meeting_id'=>$managerInfo['meeting_id'],'dis_uuid'=>$managerInfo['uuid'],'uuid'=>$meeting['manager']]);
                }
            }
        }
        $managerInfo['meeting_id'] = $meeting_id;
        $managerInfo['username'] = $params['username'];
        $meeting = [
            'meeting_id' => $meeting_id,
            'name' => $params['name'],
            'manager' => $params['uuid'],
            'manager_info'=> $managerInfo,
            'monitor'=>$params['monitor'],
            'created_at'=>time(),
            'members' => []
        ];

        $redis->hset(OnlineMeeting, $meeting_id, serialize($meeting));
        $redis->hset(OnlineFDToDevice, $GLOBALS['fd'], serialize($managerInfo));


        $redisHandel->put($redis);
        Server::successSend($GLOBALS['fd'], $meeting, MeetingCreateSuccess);
    }


    private function createMeetingId(){
        $meeting_id = mt_rand(10000,99999);
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
        if (count(array_diff(['meeting_id', 'uuid','username'], array_keys($params))) > 0) {
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);return;
        }

        $redisHandel = Redis::getInstance();
        $redis = $redisHandel->get();

        $meeting = $redis->hget(OnlineMeeting, $params['meeting_id']);

        if ($meeting) {//会议记录是否存在
            $member = $redis->hget(OnlineFDToDevice, $GLOBALS['fd']);
            $member = unserialize($member);
            //检测成员信息是否还存在其他会议中，
            if ($member['meeting_id']){
                $oldMeeting = $redis->hget(OnlineMeeting,$member['meeting_id']);
                $oldMeeting = $oldMeeting?unserialize($oldMeeting):[];
                if ($oldMeeting){
                    if ($oldMeeting['manager']==$member['uuid']){
                        $this->dissolveAction(['meeting_id'=>$member['meeting_id'],'uuid'=>$member['uuid']]);
                    }else{
                        $this->quitAction(['meeting_id'=>$member['meeting_id'],'dis_uuid'=>$member['uuid'],'uuid'=>$oldMeeting['manager']]);
                    }
                }
            }

            //修改成员信息
            $member['meeting_id'] = intval($params['meeting_id']);
            $member['username'] = $params['username'];
            $redis->hset(OnlineFDToDevice, $GLOBALS['fd'], serialize($member));

            //修改会议信息
            $meeting = unserialize($meeting);
            $meeting['members'][$params['uuid']] = $member;
            $redis->hset(OnlineMeeting, $params['meeting_id'], serialize($meeting));

            //发送通知
            Server::successSend($redis->hget(OnlineDeviceToFd, $meeting['manager']), $member, FlushMeetingMembersJoin);//发送通知给主持人
            foreach ($meeting['members'] as $uuid => $info) {
                if ($uuid == $member['uuid']){
                    Server::successSend($redis->hget(OnlineDeviceToFd, $uuid), $meeting, JoinMeetingSuccess);//发送通知给专家
                    continue;
                }
                Server::successSend($redis->hget(OnlineDeviceToFd, $uuid), $member, FlushMeetingMembersJoin);//发送通知给专家
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
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);return;
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
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);return;
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
                    $memberForMessage = $disMember;
                    //修改链接用户的信息，删除会议信息
                    $disMember['meeting_id'] = null;
                    $disMember['username'] = null;

                    $disFd = $redis->hget(OnlineDeviceToFd, $params['dis_uuid']);
                    $redis->hset(OnlineFDToDevice, $disFd, serialize($disMember));
                    //删除用户信息
                    unset($meeting['members'][$params['dis_uuid']]);
                    $redis->hset(OnlineMeeting, $params['meeting_id'], serialize($meeting));

                    foreach ($meeting['members'] as $uuid => $info) {
                        Server::successSend($redis->hget(OnlineDeviceToFd, $uuid), $memberForMessage, FlushMeetingMembersQuit);
                    }
                    Server::successSend($disFd, [], QuitMeetingSuccess);

                    if (isset($params['is_disconnect']) && $params['is_disconnect'] == true) {//兼容断线
                        Server::successSend($redis->hget(OnlineDeviceToFd, $meeting['manager']), $memberForMessage, PromiseQuitMeetingSuccess);
                    } else {
                        Server::successSend($GLOBALS['fd'], $memberForMessage, PromiseQuitMeetingSuccess);
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
            Server::failedSend($GLOBALS['fd'], [], ParamsRequiredError);return;
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