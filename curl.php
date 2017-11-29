<?php

disBindWechat(['aaaaaaaaa']);

function disBindWechat($openids)
    {
        $url = 'http://demo.zztgood.cn/wechat/disbind';
 	$postData = $openids;
        $ch = curl_init();
//	curl_setopt($ch,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PORT, 80);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	$output = curl_exec($ch);
	curl_close($ch);
        var_dump($output);
    }

?>
