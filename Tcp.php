<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-8-20
 * Time: 下午2:43
 */
include "run.php";

class Server
{

    private $server;

    private $config = [
        'open_tcp_keepalive'=>1,
        'tcp_keepidle'=>30,
        'tcp_keepcount'=>2,
        'tcp_keepinterval'=>10,
        'daemonize' => true,
        'worker_num'=>2,
        'task_worker_num'=>2,
        'log_file' => __DIR__.'/ceshi.log'
    ];


    public function __construct()
    {
        $this->server = new swoole_server('0.0.0.0', 9502);
        $this->server->set($this->config);

        $this->server->on('Connect', array($this, "OnConnect"));
        $this->server->on('Close', array($this, "OnClose"));
        $this->server->on('Receive', array($this, "OnReceive"));
        $this->server->on('Task', array($this, "OnTask"));
        $this->server->on('Finish', array($this, "OnFinish"));
        $this->server->on("WorkerStart",array($this,"OnWorkerStart"));
        $this->server->start();
    }


    public function OnConnect($serv, $fd)
    {
        echo "has connected fd=>$fd,\n";
    }

    public function OnClose($serv, $fd)
    {
        echo "has disConnected fd = $fd,\n";
        $serv->index->run('user/disconnect',[]);
    }

    public function OnReceive($serv,$fd,$from_id,$data){
        $this->copyGlobal($serv,$fd,$from_id);

        $data = decode($data);
        foreach ($data as $val){
            $val = json_decode($val,true);
            if(is_array($val) && !empty($val)){
                $serv->task($val);
            }
        }


//        echo "has Received form $fd=> $data","\n";
//        $data = json_decode($data,true);
//        $data['params']['fd'] = $fd;
//
//        if(isset($data['path'])&&$data['params']){
//            $serv->index->run($data['path'],$data['params']);
//        }
//        echo "Receive 执行结束 \n";

    }

    public function onTask($serv,$task_id,$from_id,$data){

        $data = $this->transRoute($data);
        if(isset($data['path'])){
            $serv->index->run($data['path'],$data['params']);
        }

        //调度任务，操作数据

    }

    public function onFinish($serv,$task_id,$data){
        echo "onFinish>>>  TaskId:$task_id,Data:$data \n";
    }

    public function onWorkerStart($serv,$work_id){
        //这里只运行一次,在work启动，task启动的时候加载文件，并且调用;如果出现exit和异常导致程序推出，这里也会出现代码重载
        $serv->index = new  \swoole\Run();


        //定时任务，删除僵尸用户数据，
        if($work_id == 0){
            $serv->tick(5*60000,function ($timer_id)use($serv){
                $serv->index->run('user/disWechat',[]);
                $serv->index->run('user/disUser',[]);
            });
        }

    }

    public function copyGlobal($serv,$fd,$from_id){
        $GLOBALS['serv'] = &$serv;
        $GLOBALS['ip'] = $serv->connection_info($fd,$from_id)['remote_ip'];
        $GLOBALS['fd'] = $fd;
        $GLOBALS['from_id'] = $from_id;
    }


    public function transRoute($data){
        $result = array();
        if(isset($data['data_type'])){
            switch ($data['data_type']){
                case 'event':
                    $result['path'] = 'event/index';
                    break;
                case 'interact_users':
                    $result['path'] = 'event/interact';
                    break;
                case 'airlink_online':
                    $result['path'] = 'user/online';
                    break;
                case 'disconnect':
                    $result['path'] = 'user/disconnect';
                    break;
            }
            unset($data['data_type']);
            $result['params'] = $data;
            $result['params']['fd'] = $GLOBALS['fd'];

        }

        return $result;
    }


}

$serverBegin = new  Server();
