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
        $start_fd = 0;
        $total_fds = array();
        while (true){
            $conn_list = $GLOBALS['serv']->connection_list($start_fd,50);
            if($conn_list===false || count($conn_list) === 0){
                echo "connection list is finished";
                break;
            }
            $start_fd = end($conn_list);
           $total_fds = array_merge($total_fds,$conn_list);
        }
       return $total_fds;
    }


    public static function fdExists($fd){
        return $GLOBALS['serv']->exist($fd);
    }
}