<?php

$client = new swoole_client(SWOOLE_SOCK_TCP);
$client->connect('127.0.0.1',9502,-1);
$array = ['path'=>'','params'=>''];
$status = 1;
while(true){
	$content = read();	

	if($content=='no'){
		$client->send(json_encode($array));
		$array = ['path'=>'','params'=>''];
		$status = 1;		
	}elseif($content=='path'){
		$status = 1;
	}elseif($content=='params'){
		$status=2;
	}else{
		if($status==2){
		$content = explode(":",$content);
		$array['params'][$content[0]]=$content[1];
		}else{
		$array['path']=$content;
		}
	}
	
	fwrite(STDOUT,"has input : ".json_encode($array)."\n");
}



function read(){
fwrite(STDOUT,"请输入参数：");
return trim(fgets(STDIN));
}

?>
