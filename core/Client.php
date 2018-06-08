<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 2018/6/7
 * Time: 9:51
 */

namespace core;


class Client
{
    private static $instance = null;

    private $client;


    public function __construct()
    {
        $this->client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        if (!$this->client->connect('127.0.0.1', 9901, 0.5))
        {
            echo "connect failed. Error: {$this->client->errCode}\n";
        }
    }


    public static function getInstance(){
        if (self::$instance===null){
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function send($data){
       $result = $this->client->send($data);
        if (!$result){ //发送失败（可能连接时间超时），重试一次，这次是在客户端重新连接之后立即执行的
            self::$instance = new self();
            $result = $this->client->send($data);
        }
        return $result;
    }


    public function close(){
        return $this->client->close();
    }


    public function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public function __wakeup()
    {
        // TODO: Implement __wakeup() method.
    }
}