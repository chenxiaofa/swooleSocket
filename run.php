<?php
namespace swoole;

include_once "core/Loader.php";

include_once ("define.php");
include_once ("function.php");
include_once (__DIR__.'/./config/redisConfig.php');


class Run{

    private $dispatch = array();

    public function dispatch($path,$params){
        $config = env("default");

        $path = explode("/",ltrim($path,'/'));

        $path = array_filter($path);

        $controller = @$path[0]?strtolower(array_shift($path)).$config['controllerExtra']:$config['defaultController'];


        $nameSpace = "Controller\\";

        $controller = $nameSpace.$controller;
        $action = @$path[0]?strtolower(array_shift($path)).$config['actionExtra']:$config['defaultAction'];

        if(!class_exists($controller)){
            die("controller <$controller> is undefined \n");
        }
        if(!method_exists($controller,$action)){
            die("action <$action> is undefined! \n");
        }
        if(!isset($this->dispatch[$controller])){
            $this->dispatch[$controller] = new $controller();
        }

        $this->dispatch[$controller]->$action($params);

    }


    public function loadClass($class){
        $filename = end(explode("\\",trim($class,"\\")));
        $controllers = APP_PATH."Controller/".$filename.".php";
        if(file_exists($controllers)){
            echo $controllers."has include \n";
            include $controllers;
        }
    }


    public  function run($path,$params){
        \core\Loader::register();
        //spl_autoload_register(array($this, 'loadClass'));
        $this->dispatch($path,$params);
    }



}