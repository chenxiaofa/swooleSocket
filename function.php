<?php
/**
 * 公共函数文件
 */


if(!function_exists("env")){
    function env($config){
        $file = APP_PATH."config/".$config.".php";
        if(file_exists($file)){
            $data = require ($file);
        }else{
            $data = array();
        }
        return $data;
    }
}

if(!function_exists("decode")){
    function decode($str,$flag=0){
        $key = 'geniusFirst1RobinXiancai.1@#$QQ2';
        $data = array_filter(explode('##',$str));
        foreach ($data as $k => $encrypted){
            $encrypted = str_replace('%3D','=',$encrypted);
            $encrypted = str_replace(' ','+',$encrypted);
            if($flag==1){
                return trim(base64_decode($encrypted));
            }

            $re = mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$key, base64_decode($encrypted), MCRYPT_MODE_ECB);
            $data[$k] = trim($re);
        }
        return $data;
    }
}