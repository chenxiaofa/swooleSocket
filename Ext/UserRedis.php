<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-11-15
 * Time: 上午11:39
 */

namespace Ext;


use core\Redis;
use Model\UserModel;

class UserRedis
{
    public static function getDeviceByFd($fd){
        $redis = Redis::getInstance()->redis();
        $device = $redis->hGet(AirLinkOnlineRecord,$fd);
        return json_decode($device,true);
    }


    public static function getFdByDevice($device){
        return Redis::getInstance()->redis()->hGet(AirLinkOnlineDevice,$device);
    }

    public static function getFdByUuid($uuid){
        return Redis::getInstance()->redis()->hGet(AirLinkOnlineUuid,$uuid);
    }

    /**
     * @param array $device 设备信息，包含device_tag和ip
     * @param $fd 链接的设备id
     */
    public static function updateOrCreateUserByDeviceAndFd(array $device,$fd){
        $redis = Redis::getInstance()->redis();
        //将device的旧fd替换掉，没有旧的就直接设置成新的
        $oldFd = $redis->hGet(AirLinkOnlineDevice,$device['device_tag']);
        echo "old fd :";var_dump($oldFd);echo "\n";
        if($oldFd){//将旧的fd替换成新的fd
            $oldDevice = $redis->hGet(AirLinkOnlineRecord,$oldFd);
            $redis->hDel(AirLinkOnlineRecord,$oldFd);
            if($oldDevice){
                $redis->hSet(AirLinkOnlineRecord,$fd,$oldDevice);
                //设置uuid的fd
                $uuid = json_decode($oldDevice,true)['uuid'];
                $redis->hSet(AirLinkOnlineUuid,$uuid,$fd);
                //设置device_tag的fd
                $redis->hSet(AirLinkOnlineDevice,$device['device_tag'],$fd);
            }
        }else{
            $newDevice = self::getUserFromDb($device['device_tag']);
            if($newDevice){
                $redis->hSet(AirLinkOnlineDevice,$device['device_tag'],$fd);
                $redis->hSet(AirLinkOnlineUuid,$newDevice['uuid'],$fd);//设置uuid
                $newDevice['device_tag'] = $device['device_tag'];
                $newDevice['start_time'] = time();
                $newDevice['ip'] = $device['ip'];
                $redis->hSet(AirLinkOnlineRecord,$fd,json_encode($newDevice));
                //统计人数峰值
                self::personNum();
            }
        }

    }

    /**
     * @param $device
     * 微信断线重连
     */
    public static function updateWechatDevice($device){
        $redis = Redis::getInstance()->redis();
        $fd = self::getFdByDevice($device);
        if($redis->hExists(AirLinkDeviceTagWechat,$device)){
            $openIds = $redis->hGet(AirLinkDeviceTagWechat,$device);
            if($openIds){
            $openIds = json_decode($openIds,true);
                foreach ($openIds as $openId){
                    $oldDevice = $redis->hGet(AirLinkWechatDevice,$openId);
                    $oldDevice = json_decode($oldDevice,true);
                    if($redis->hExists(AirLinkDeviceWechat,$oldDevice['fd'])){
                        $redis->hDel(AirLinkDeviceWechat,$oldDevice['fd']);
                    }
                    $redis->hSet(AirLinkWechatDevice,$openId,json_encode(['fd'=>$fd,'time'=>$oldDevice['time']]));
                }
                $redis->hSet(AirLinkDeviceTagWechat,$device,json_encode($openIds));
                $redis->hSet(AirLinkDeviceWechat,$fd,json_encode($openIds));
            }
        }
    }


    /*
     * 统计人数峰值
     */
    public static function personNum(){
        if(Redis::getInstance()->redis()->hLen(AirLinkOnlineRecord)>Redis::getInstance()->redis()->get(AirLinkTopUser)){
            Redis::getInstance()->redis()->Set(AirLinkTopUser,intval(Redis::getInstance()->redis()->get(AirLinkTopUser))+1);
        }
    }



    public static function getUserFromDb($device){
        $userModel = new UserModel();
        $sql = "select `users`.`id` as user_id ,`users`.`uuid` as uuid,`user_apps`.`app_version` as app_version,`user_apps`.`app_edition` as app_edition from `users` RIGHT JOIN user_apps on users.id = user_apps.user_id WHERE device_tag= '{$device}' and `users`.app_id=1 limit 1 ";
        return current($userModel->execute($sql));
    }



}