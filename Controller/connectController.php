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
        
        public function disconnectAction($params){
            // fd,
            $redisHandel = Redis::getInstance();
            $redis = $redisHandel->get();

            $device = $redis->hget(OnlineFDToDevice,$params['fd']);
            if ($device) $device = unserialize($device); else return ;
            $delFd = $redis->hget(OnlineDeviceToFd,$device['uuid']);

            if ($delFd==$params['fd']){
                //判断是否存在会议，如果存在会议，则给定一个异常断开的状态
                    $redis->hdel(OnlineDeviceToFd,$device['uuid']);
                    $redis->hdel(OnlineFDToDevice,$params['fd']);
            }
            
            //分发用户详细信息
            
            $redisHandel->put($redis);

        }


}