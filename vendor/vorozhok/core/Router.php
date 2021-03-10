<?php

// Класс маршрутизации

namespace vorozhok;

class Router
{
    
    protected static $routes = [];// Сюда записываем имеющиеся на сайте маршруты
    protected static $route = [];// Сюда записывается текущий маршрут, если найдено соответствие запрошенного адреса с таблицей маршрутов
    
    // Данный метод будет записывать правила в таблицу маршрутов
    public static function add($regexp, $route = []){
        self::$routes[$regexp] = $route;//Просто добавляем в свойство с ключем - регулярным выраждением соответствие нашего маршрута
    }
    //Методы для дебага
    public static function getRoutes(){
        return self::$routes;
    }
    
    public static function getRoute(){
        return self::$route;
    }
    //Метод для работы с url
    public static function dispatch($url){
        $url = self::removeQueryString($url);
        if(self::matchRoute($url)){
			$names['prefix'] = self::$route['prefix'];
			$names['controller'] = self::$route['controller'];
            $controller = "app\controllers\\{$names['prefix']}{$names['controller']}Controller";// Тут мы идем по пути namespace'ов а не директорий!
            if(class_exists($controller)){
                $controllerObject = new $controller(self::$route);
                $action = self::lowerCamelCase(self::$route['action']) . 'Action';
                if(method_exists($controllerObject, $action)){
                    $controllerObject->$action();
                    $controllerObject->getView();
                }else{
                    throw  new \Exception("Method $action not found.", 404);
                }
            }else{
                throw new \Exception("Controller $controller not found.", 404);
            }
        }else{
            throw new \Exception('Страница не найдена', 404);
        }
    }
    //Метод для поиска соответствия адреса таблице маршрутов
    public static function matchRoute($url){
        foreach(self::$routes as $pattern => $route){
            if(preg_match("#{$pattern}#", $url, $matches)){
                foreach($matches as $k => $v){
                    if(is_string($k)){
                        $route[$k] = $v;
                    }
                }
                if(empty($route['action'])){
                    $route['action'] = 'index';
                }
				if(isset($route['query']) && strpos($route['query'], '/')){
					$q = explode('/', $route['query']);// page/1/param/2 to ['page', '1', 'param', 2]
					
					// ['page', '1', 'param', 2] to ['page' => '1','param' => '2']
					$assoc = [];
					for($i=0; $i<count($q); $i++){
						if(($i % 2) == 0){
							$assoc[$q[$i]] = $q[$i+1];
						}
					}
					$route['query'] = $assoc;
				}
                if(!isset($route['prefix'])){
                    $route['prefix'] = '';
                }else{
                    $route['prefix'] .= '\\';
                }
                $route['controller'] = self::upperCamelCase($route['controller']);
                self::$route = $route;
                return true;
            }
        }
        return false;
    }
    // CamelCase for controllers
    protected static function upperCamelCase($name){
        $name = str_replace(' ', '',ucwords(str_replace('-', ' ', $name)));
        return $name;
    }
    //snakeCase for Actions
    protected static function lowerCamelCase($name){
        return lcfirst(self::upperCamelCase($name));
    }
    
    protected static function removeQueryString($url){
        if($url){
            if(!strpos($url,'?')){
				return $url;
			}else{
				if(strpos($url,'/?')){
					return strstr($url, '/?', true);
				}else{
					return strstr($url, '?', true);
				}
			}
        }
    }
}