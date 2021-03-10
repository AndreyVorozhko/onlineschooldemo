<?php

namespace vorozhok\libs;

class ArticlePagination extends Pagination{

    public function getParams(){
        $route = \vorozhok\Router::getRoute();
		$pattern = '%^(.*)page\/[0-9]*$%';
		//debug($_SERVER);
		$res = preg_match($pattern, $_SERVER['REQUEST_URI'], $matches);
		
		$uri = ($res == true) ? $matches[1] : "{$_SERVER['REQUEST_URI']}/";
        return $uri;
    }

}