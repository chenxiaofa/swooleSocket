<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-11-14
 * Time: 下午5:55
 */

namespace Ext;
class Server
{
    public static function send($str,$fd=0){
        $GLOBALS['serv']->send($fd!==0?$fd:$GLOBALS['fd'],$str);
    }

    /**
     * @return array
     * 获取所有的fd
     */
    public static function getConnections(){
       return $GLOBALS['serv']->connection_list();
    }


    public static function fdExists($fd){
        return $GLOBALS['serv']->exists($fd);
    }
}