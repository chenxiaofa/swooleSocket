<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-11-15
 * Time: 下午5:25
 */

namespace Controller;


use core\Redis;
use Ext\UserRedis;
use Model\EventModel;
use Model\InteractUser;
use Model\UserEventModel;

class eventController extends baseController
{
    /**
     * @param $params
     * 基础事件
     */
    public function indexAction($params){
        $event_id = $this->getEventByName($params['event_name']);
        $device = UserRedis::getDeviceByFd($params['fd']);
        if($device&&$event_id){//如果存在设备
            UserEventModel::add([
                'user_id'=>$device['user_id'],
                'event_id'=>$event_id,
                'app_version'=>$device['app_version'],
                'app_edition'=>$device['app_edition'],
                'event_value'=>@$params['event_value'],
                'created_at' =>time(),
                'updated_at' =>time()
            ]);
        }

    }

    /**
     * @param $params
     * 投影事件
     */
    public function interactAction($params){
        $device = UserRedis::getDeviceByFd($params['fd']);
        $otherFd = UserRedis::getFdByUuid($params['link_device_tag']);
        $otherDevice = UserRedis::getDeviceByFd($otherFd);
        if($params['interact_status']== 'start'){//开始投影
            if($device && $otherDevice && $otherFd){
                $record = [
                    'user_id'=>$device['user_id'],
                    'interact_user_id'=>$otherDevice['user_id'],
                    'is_host'=>$params['is_host'],
                    'ip_addr'=>$GLOBALS['ip'],
                    'start_time'=>time()
                ];
                Redis::getInstance()->redis()->hSet(AirLinkInteractRecord,$params['fd'],json_encode($record));
		$this->topInteractHour();
            }
        }else{//结束投影
            $interact = json_decode(Redis::getInstance()->redis()->hGet(AirLinkInteractRecord,$params['fd']),true);
            if($interact){
                Redis::getInstance()->redis()->hDel(AirLinkInteractRecord,$params['fd']);
                $interact['end_time'] = time();
                //$interact['ip_addr'] = $GLOBALS['ip'];
                InteractUser::add($interact);
            }
        }

    }




    //获取投影事件，
    private function getEventByName($name){
        if($this->redis->exists(AirLinkEvents)){
            return $this->redis->hGet(AirLinkEvents,'id'.$name);
        }else{
           $events =  EventModel::select();
           foreach ($events as $event){
               $this->redis->hSet(AirLinkEvents,$event['name'],$event['id']);
           }
            return $this->redis->hGet(AirLinkEvents,'id'.$name);
        }
    }


    private function topInteractHour(){
        $interactNum = $this->redis->hLen(AirLinkInteractRecord);
        $topInteract = $this->redis->hGet(AirLinkTopInteract,intval(date("H")));
        if($interactNum > $topInteract){
            $this->redis->hSet(AirLinkTopInteract,intval(date("H")),$interactNum);
        }

    }
}