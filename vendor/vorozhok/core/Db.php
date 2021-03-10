<?php

namespace vorozhok;

class Db
{

    use TSingletone;
    
    protected function __construct(){
        $db = require CONF . '/config_db.php';
        class_alias('\RedBeanPHP\R', '\R');
        \R::setup($db['dsn'], $db['user'], $db['pass']);
        if( !\R::testConnection() ){
            throw new \Exception('No DataBase Connection', 500);
        }
        if(DEBUG){
            \R::debug(TRUE, 3);
        }else{
            \R::debug(FALSE);
			\R::freeze(TRUE);
		}
		
		
    }
    
}