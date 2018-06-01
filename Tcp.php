<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-8-20
 * Time: 下午2:43
 */
class Server
{

    private $server;

    private $config = [
        'open_tcp_keepalive' => 1,
        'tcp_keepidle' => 30,
        'tcp_keepcount' => 2,
        'tcp_keepinterval' => 10,
        'daemonize' => true,
        'worker_num' => 2,
        'log_level' => 3,
//        'open_eof_split' => true,
//        'package_eof' => "\r\n",
        'log_file' => __DIR__ . '/ceshi.log'
    ];


    public function __construct()
    {
        $this->server = new swoole_server('0.0.0.0', 9901);
        $this->server->set($this->config);

        $this->server->on('Connect', array($this, "OnConnect"));
        $this->server->on('Close', array($this, "OnClose"));
        $this->server->on('Receive', array($this, "OnReceive"));
        $this->server->on('Task', array($this, "OnTask"));
        $this->server->on('Finish', array($this, "OnFinish"));
        $this->server->on("WorkerStart", array($this, "OnWorkerStart"));
        $this->server->start();
    }


    public function OnConnect($serv, $fd)
    {
        echo "has connected fd=>$fd,\n";
    }

    public function OnClose($serv, $fd)
    {

        $this->copyGlobal($serv, $fd);
        $serv->index->run('connect/disconnect', ['fd' => $fd]);
        echo "has disConnected fd = $fd,\n";
    }

    public function OnReceive($serv, $fd, $from_id, $data)
    {
            register_shutdown_function("handleFatal",$serv,$fd);
            var_dump($data);
	    $data = json_decode(trim($data),true);
            var_dump($data);
            if (isset($data['path']) && $data['params']) {
                $this->copyGlobal($serv, $fd);
                $serv->index->run($data['path'], $data['params']);
            }
            echo "Receive 执行结束 \n";

    }

    public function onTask($serv, $task_id, $from_id, $data)
    {
        echo "onTask>>>  TaskId:$task_id, FromId:$from_id,Data:$data \n";
        //调度任务，操作数据
    }

    public function onFinish($serv, $task_id, $data)
    {
        echo "onFinish>>>  TaskId:$task_id,Data:$data \n";
    }

    public function onWorkerStart($serv, $work_id)
    {

        include "run.php";
        $serv->index = new  \swooleSocket\Run();


        if ($work_id == 0) {
            $serv->tick(60000, function (
                $timer_id
            ) use ($serv) {
              //  $serv->index->run('dirtyDataHandel/timingDel', []);
            });
        }


    }

    public function copyGlobal($serv, $fd)
    {
        $GLOBALS['serv'] = &$serv;
        $GLOBALS['fd'] = $fd;
        //$GLOBALS['from_id'] = $from_id;
    }



}

$serverBegin = new  Server();
