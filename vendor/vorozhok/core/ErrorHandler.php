<?php

namespace vorozhok;

class ErrorHandler
{
    
    public function __construct(){
        if(DEBUG){
            error_reporting(-1);
        }else{
            error_reporting(0);
        }
    set_exception_handler([$this, 'exceptionHandler']);// тут мы назначили функцию, которой мы будем ловить и обрабатывать ошибки
    }
    public function exceptionHandler($e){
        $this->logErrors($e->getMessage(), $e->getFile(), $e->getLine(),$e->getCode());
        $this->displayError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    }
    protected function logErrors($message = '', $file = '', $line = '', $code =''){
        error_log("[" . date('Y-m-d H:i:s') . "] Error text: {$message} in file: {$file} in string number: {$line} | error code: {$code}\n===========\n", 3, ROOT . '/tmp/errors.log');
    }
    
    protected function displayError($errno, $errstr, $errfile, $errline, $response = 404){
        //$response - это код ошибки, которую надо передать браузеру
        http_response_code($response);//Отправляем браузеру нужный заголовок
        //Красивую 404 страницу покажем только если включен режим разработки:
        if($response == 404 && !DEBUG){
            require WWW . '/errors/404.php';
            exit;
        }
        if(DEBUG){
            require WWW . '/errors/dev.php';
        }else{
            require WWW . '/errors/prod.php';
        }
        exit;
    }
}