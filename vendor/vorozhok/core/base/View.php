<?php

namespace vorozhok\base;

use vorozhok\stdData;
use vorozhok\App;

class View
{

    //массив с текущим маршрутом
    public $route;
    
    public $controller;
    public $model;
    public $view;
    public $prefix;
    public $layout;
    public $data = [];
    public $meta = [];
    
    public function __construct($route, $layout = '', $view = '', $meta, $queries = false){
        $this->route = $route;
        $this->controller = $route['controller'];
        $this->view = $view;
        $this->model = $route['controller'];
        $this->prefix = $route['prefix'];
        $this->meta = $meta;
        if($layout === false){
            $this->layout = false;
        }else{
            $this->layout = $layout ?: LAYOUT;
        }
		$this->queries = $queries;
    }
    
    public function render($data){
		
		// Now we must allow querries to prevent pages with same content (dublicates)
		if(!$this->queries && isset($this->route['query'])){
			if(is_array($this->route['query'])){
				$query = print_r($this->route['query'], true);
			}else{
				$query = $this->route['query'];
			}
			throw new \Exception("Query detected: `$query` Queries are not allowed for this page.", 404);
		}

		if($this->prefix != ''){
			$prefix = substr_replace($this->prefix,DS,-1);
		}else{
			$prefix = $this->prefix;
		}
		
		$tpl_ext = '.html';
		$load_dir = APP . "/views/{$prefix}{$this->controller}";
		if(is_dir($load_dir) && is_file($load_dir . DS . "$this->view$tpl_ext")){
			$m = new \Mustache_Engine(array('entity_flags' => ENT_QUOTES, 'partials_loader' => new \Mustache_Loader_FilesystemLoader(APP . "/views/layouts/{$this->layout}",['extension'=>$tpl_ext])));
			$loader = new \Mustache_Loader_FilesystemLoader($load_dir, ['extension'=>$tpl_ext]);
		
		
		$tpl = $loader->load($this->view);
		
		//Мета теги
        $metatags = $this->getMeta();
		// Общие данные, пепедаваемые в вид из AppController'а (могут быть только массивом)
		$common = App::$reg->getProperty('common') ?? [];
		
		if(is_array($data)){
			$data['metatags'] = $metatags;
			$data['index'] = PATH;
			$data['adminname'] = ADMINNAME;
			$data = array_merge($data, $common);
		}elseif(is_object($data) && ($data instanceof StdData)){
			$data->setProp('metatags', $metatags);
			$data->setProp('index', PATH);
			$data->setProp('adminname', ADMINNAME);
			foreach($common as $k=>$v){
				$data->setProp($k, $v);
			}
		}else{
			throw new \Exception("Data for view must be an object of StdData class or array");
		}
		echo $m->render($tpl, $data);
		}
    }
    
    public function getMeta(){
        $metatags = <<<META
<title>{$this->meta['title']}</title>
    <meta name="description" content="{$this->meta['desc']}">
    <meta name="keywords" content="{$this->meta['keywords']}">

META;
    return $metatags;
    }
}