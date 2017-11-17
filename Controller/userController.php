<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-11-15
 * Time: 上午11:19
 */

namespace Controller;


use core\Redis;
use Ext\UserRedis;
use Model\UserModel;
use Model\userOnlineModel;

class userController extends baseController
{
    public function onlineAction($params){
       UserRedis::updateOrCreateUserByDeviceAndFd(['device_tag'=>$params['device_tag'],'ip'=>@$params['ip']],$params['fd']);
        UserRedis::updateWechatDevice($params['device_tag']);
        echo "onlineAction has complete \n";
    }

    public function disconnectAction($params){
        Redis::getInstance()->redis()->hDel(AirLinkOnlineRecord,$params['fd']);
        $this->storeOnlineRecordByFd($params['fd']);

        //删除微信用户绑定的fd
        $this->delWechatDeviceBind($params['fd']);
    }


    public function storeOnlineRecordByFd($fd){
        $user = Redis::getInstance()->redis()->hGet(AirLinkOnlineRecord,$fd);
        if($user){
            $user = json_decode($user,true);
            userOnlineModel::add(['start_time'=>$user['start_time'],'end_time'=>time(),'user_id'=>$user['user_id'],'ip_addr'=>$user['ip']]);
        }
    }

    public function delWechatDeviceBind($fd){
        if($this->redis->hExists(AirLinkDeviceWechat,$fd)){
            $openids = $this->redis->hget(AirLinkDeviceWechat,$fd);
            $openids = json_decode($openids,true);
            $this->redis->hdel(AirLinkDeviceWechat,$fd);
            $this->disBindWechat($openids);//需要做通知威信用户，所以不能直接删除
        }
    }


    public function disBindWechat($openids){
            $config = env('default');
            $url = $config['hostname'].'/wechat/disbind';
            $postData = $openids;
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_PORT,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
            $output = curl_exec($ch);
            curl_close($ch);
    }


}