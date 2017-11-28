<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-11-15
 * Time: 上午11:19
 */

namespace Controller;


use core\Redis;
use Ext\Server;
use Ext\UserRedis;
use Model\InteractUser;
use Model\UserModel;
use Model\userOnlineModel;

class userController extends baseController
{
    public function onlineAction($params){
        UserRedis::updateOrCreateUserByDeviceAndFd(['device_tag'=>$params['device_tag'],'ip'=>@$GLOBALS['ip']],$params['fd']);
        UserRedis::updateWechatDevice($params['device_tag']);
    }

    public function disconnectAction($params){
        //删除微信用户绑定的fd
        $this->delWechatDeviceBind($params['fd']);
        //投影事件删除
        $this->disInteract($params['fd']);
        // 保存用户登录信息, 并删除用户登录信息
        $this->storeOnlineRecordByFd($params['fd']);
    }

    public function wechatAction($params){
        echo "用户发送wechat：";
        var_dump($params);
        echo "\n";
        if(Server::fdExists($params['to_fd'])){
            Server::send(json_encode($params['content']),$params['to_fd']);
        }
    }


    public function disWechatAction(){

        $wechatDevice = Redis::getInstance()->redis()->hGetAll('airlink_wechat_device');
        $deleteWechats = [];
        foreach ($wechatDevice as $wechat=> $fd){
            $fd = json_decode($fd,true);
            if($fd['time']+30*60<time()){
                $deleteWechats[] = $wechat;
                if(Server::fdExists($fd['fd'])){
                    Server::send(json_encode(['type'=>'disbind','openid'=>$wechat]),$fd['fd']);
                }
            }
        }

        echo "请求断开数量：",count($deleteWechats),"\n";

        if(count($deleteWechats) >0){
            $this->disBindWechat($deleteWechats);
        }

    }

    public function disUserAction(){//删除没有链接的user
        $connections = Server::getConnections();
        $userFds = Redis::getInstance()->redis()->hKeys(AirLinkOnlineRecord);
        echo "dis user";
        var_dump($connections);
        var_dump($userFds);
        echo "\n";
        if(count($connections)>0 && count($userFds)>0){
            $diff = array_diff($userFds,$connections);
            echo "diff is :\n";var_dump($diff);
            foreach ($diff as $fd){
                //删除用户数据
                $user = Redis::getInstance()->redis()->hGet(AirLinkOnlineRecord,$fd);
                Redis::getInstance()->redis()->hDel(AirLinkOnlineRecord,$fd);
                $user = json_decode($user,true);
                Redis::getInstance()->redis()->hDel(AirLinkOnlineDevice,$user['device_tag']);
                Redis::getInstance()->redis()->hDel(AirLinkOnlineUuid,$user['uuid']);
                //删除投影记录
                Redis::getInstance()->redis()->hDel(AirLinkInteractRecord,$fd);
            }
        }
        //还需要删除投影记录，

    }


    public function storeOnlineRecordByFd($fd){
        echo "storeOnlineRecord\n";
        $user = Redis::getInstance()->redis()->hGet(AirLinkOnlineRecord,$fd);
        if($user){
            Redis::getInstance()->redis()->multi();
            //存储用户信息
            $user = json_decode($user,true);
            userOnlineModel::add(['start_time'=>$user['start_time'],'end_time'=>time(),'user_id'=>$user['user_id'],'ip_addr'=>$user['ip']]);
            //删除临时存储的用户信息
            Redis::getInstance()->redis()->hDel(AirLinkOnlineRecord,$fd);
            Redis::getInstance()->redis()->hDel(AirLinkOnlineDevice,$user['device_tag']);
            Redis::getInstance()->redis()->hDel(AirLinkOnlineUuid,$user['uuid']);
            Redis::getInstance()->redis()->exec();
            }
    }

    public function delWechatDeviceBind($fd){
        if($this->redis->hExists(AirLinkDeviceWechat,$fd)){
            $openids = $this->redis->hget(AirLinkDeviceWechat,$fd);
            $openids = json_decode($openids,true);
            //$this->redis->hdel(AirLinkDeviceWechat,$fd);
            $device = UserRedis::getDeviceByFd($fd);
            $this->redis->hDel(AirLinkDeviceTagWechat,$device['device_tag']);
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


    public function disInteract($fd){
        $interact = json_decode(Redis::getInstance()->redis()->hGet(AirLinkInteractRecord,$fd),true);
        if($interact){
            Redis::getInstance()->redis()->hDel(AirLinkInteractRecord,$fd);
            $interact['end_time'] = time();
            InteractUser::add($interact);
        }

    }


}