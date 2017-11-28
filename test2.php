<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-11-24
 * Time: 上午10:07
 */

$client = new swoole_client(SWOOLE_SOCK_TCP);
$client->connect('127.0.0.1',9502,-1);
$device_tag = $argv[1];
$event = ['data_type'=>'event','event_name'=>34];
$interact = ['data_type'=>'interact_users',
    'interact_status'=>'start',
    'link_device_tag'=>'00009357-bac5-4664-a8b2-d4a4ac9469de',
    'is_host'=>1,
    'app_id'=>1
];

$array1 = ['data_type'=>'airlink_online','device_tag'=>$device_tag];

$client->send(json_encode($array1));
sleep(20);
//for($i=0;$i<1;$i++){
//    usleep(100000);
//    $client->send(json_encode($interact));
//}

sleep(100000);