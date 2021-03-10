<?php

namespace vorozhok\base;

abstract class Controller
{
    
    //массив с текущим маршрутом
    public $route;
    
    public $controller;
    public $model;
    public $view;
    public $prefix;
    public $layout;
    public $data = [];
    public $meta = ['title' => '', 'desc' => '', 'keywords' => ''];
	public $queries;

    public function __construct($route){
        $this->route = $route;
        $this->controller = $route['controller'];
        $this->model = $route['controller'];
        $this->view = $route['action'];
        $this->prefix = $route['prefix'];
    }
    
    public function getView(){
        $viewObject = new View($this->route, $this->layout, $this->view, $this->meta, $this->queries);
        $viewObject->render($this->data);
    }
    
    public function set($data){
        $this->data = $data;
    }
    
    public function setMeta($title = '', $desc = '', $keywords = ''){
        $this->meta['title'] = $title;
        $this->meta['desc'] = $desc;
        $this->meta['keywords'] = $keywords;
    }
	
	// Now we must allow querries to prevent pages with same content (dublicates)
	public function allowQueries($allow = false){
		$this->queries = $allow;
	}
}