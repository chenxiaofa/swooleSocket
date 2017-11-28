<?php

$file = fopen("./test.sql","r");
$i = 0;
$arr = [];
while (!feof($file)){
	$device = trim(fgets($file));
	$arr[$i] = popen("/usr/local/bin/php test2.php  ".$device. " &","r");
	echo $i++,"完成！ \n";
	if($i==20){
		break;
	}
}

sleep(200);
foreach ($arr as $key => $val){
	usleep(100000);
	echo $key ,"has closed ; \n";
	fclose($val);
}
//while(true){
//	$content = read();
//
//	if($content=='no'){
//		$client->send(json_encode($array));
//		$array = ['path'=>'','params'=>''];
//		$status = 1;
//	}elseif($content=='path'){
//		$status = 1;
//	}elseif($content=='params'){
//		$status=2;
//	}else{
//		if($status==2){
//		$content = explode(":",$content);
//		$array['params'][$content[0]]=$content[1];
//		}else{
//		$array['path']=$content;
//		}
//	}
//
//	fwrite(STDOUT,"has input : ".json_encode($array)."\n");
//}
//
//
//
//function read(){
//fwrite(STDOUT,"请输入参数：");
//return trim(fgets(STDIN));
//}

?>
