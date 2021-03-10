<?php

namespace vorozhok;

class App
{
    public static $reg;
    
    public function __construct(){
		
		$ruri = $_SERVER['REQUEST_URI'];
		if(strlen($ruri) > 1 && $ruri[strlen($ruri) - 1] == '/'){
			redirect(PATH . trim($_SERVER['REQUEST_URI'], '/'));
		}

        $request = trim($_SERVER['REQUEST_URI'], '/');
		session_name('sid');
        session_start();
        self::$reg = Registry::instance();
        $this->getParams();
        new ErrorHandler();
        Router::dispatch($request);
    }
    
    protected function getParams(){
        $params = require CONF . '/params.php';
        if(!empty($params)){
            foreach($params as $k => $v){
                self::$reg->setProperty($k, $v);
            }
        }
    }
}