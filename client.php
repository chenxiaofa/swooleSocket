<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-5-3
 * Time: 下午5:17
 */

//$redis = new \Swoole\Coroutine\Redis();
//$connect = $redis->connect('127.0.0.1',6379);
//var_dump($connect);

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

$uuid = '';

$client->on("connect", function($cli)use(&$uuid) {
    echo "链接成功！！！\r\n";

echo "please input uuid:";

$uuid = trim(fgets(STDIN));

//file_put_contents('uuid.text',$uuid);
$cli->send(initDevice($uuid)."\r\n");

echo "已初始化，请输入选项（create，join，quit，dissolve，end）：\r\n";

while ($str = trim(fgets(STDIN))){
    execWhile($str,$cli,$uuid);
}

});

function execWhile($str,$cli,$uuid){
    switch ($str){
        case "create":
            $cli->send(createMeeting($uuid)."\r\n");break;
        case "join":
            $cli->send(joinMeeting($uuid)."\r\n");break;
        case "quit":
            fwrite(STDOUT,"你是manager:{$uuid}，请输入需要退出的uuid：");
            $dis_uuid = trim(fgets(STDIN));
            $cli->send(quitMeeting($uuid,$dis_uuid)."\r\n");break;
        case "dissolve":
            $cli->send(dissolveMeeting($uuid)."\r\n");break;
        case 'end':
            $cli->close();
            echo "端口已经关闭。。。";
            sleep(2);
            exit;
        default:
            echo "没有这个参数！！！";
            break;
    }

    //$cli->send($str."\r\n");
    //fwrite(STDOUT,"请输入选项：\r\n");
}


$client->on("receive", function($cli, $data)use(&$uuid){
  fwrite(STDOUT,$data."\r\n");
  while (true){
      fwrite(STDOUT,"请输入选项（create，join，quit，dissolve，end）：\r\n");
      $str = trim(fgets(STDIN));
      execWhile($str,$cli,$uuid);
  }
});
$client->on("error", function($cli){
    echo "connect failed\n";
});
$client->on("close", function($cli){
    echo "connection close\n";
});

$client->connect("127.0.0.1", 9502, 0.5);



function initDevice($uuid){
    $data = [
        'path'=>'index/init'
        ,'params'=>[
            'uuid'=>$uuid,
        ]
    ];
var_dump(serialize($data));
    return serialize($data);
    //$client->send(serialize($data.'\r\n'));
}


function createMeeting($uuid){
    $data = [
        'path'=>'meeting/create',
        'params'=>[
            'name'=>'测试会议',
            'meeting_id'=>'test-meeting',
	        'uuid'=>$uuid
        ]
    ];
    
    return serialize($data);
}


function joinMeeting($uuid){
    $data = [
	
	'path'=>'meeting/join',
	'params'=>[
		'meeting_id'=>'test-meeting',
		'uuid'=>$uuid	
	]
    ];

    return serialize($data);
}


function quitMeeting($uuid,$disuuid){
	$data = [
		'path'=>'meeting/quit',
		'params'=>[
		'meeting_id'=>'test-meeting',
		'uuid'=>$uuid,
		'dis_uuid'=>$disuuid
	],
	];
    return serialize($data);
}


function dissolveMeeting($uuid){

    $data = [
        'path'=>'meeting/dissolve',
        'params'=>[
            'meeting_id'=>'test-meeting',
            'uuid'=>$uuid
        ]
    ];

    return serialize($data);

}

