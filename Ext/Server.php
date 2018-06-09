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
    public static function send($data,$fd=0){
        echo "send fd=>Data:{$fd}=>{$data} \n";
        echo "debug :";
        print_r(debug_backtrace(2));
        echo "\n";
        if (self::fdExists($fd)){
            $GLOBALS['serv']->send($fd!==0?$fd:$GLOBALS['fd'],$data);
        }else{
            echo "fd=>{$fd} is not found ! \n";
        }

    }

    public static function successSend($fd,$data=[],$code =SuccessMessage, $message='success'){
        $message = ['code' => $code,'message'=>$message,'data'=>$data];
        self::send(json_encode($message),$fd);
    }

    public static function failedSend($fd,$data=[],$code=FatalError,$message='failed'){
        $message = ['code'=>$code,'message'=>$message,'data'=>$data];
        self::send(json_encode($message),$fd);
    }

    public static function getConnections(){
        $start_fd = 0;
        $total_fds = [];
        while (true){
            $conn_list = $GLOBALS['serv']->connection_list($start_fd,50);
            if ($conn_list === false || count($conn_list) ===0 ){
                echo "connection list is end \n";
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