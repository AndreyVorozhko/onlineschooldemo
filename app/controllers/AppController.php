<?php

namespace app\controllers;

use app\models\AppModel;
use vorozhok\base\Controller;
use vorozhok\App;
use vorozhok\Cache;
use app\service\Service;

class AppController extends Controller
{
    public function __construct($route){
        parent::__construct($route);
        
        new AppModel();

		App::$reg->setProperty('cats', self::cacheCategory());
		
		$ds = DS;
		
		$common = [];
		$common['base'] = PATH;
		$common['city'] = Service::cooCity();
		$common['captcha'] = base64_encode(Service::capGen());
		$common['if_signed'] = Service::isAuth();
		$common['if_admin'] = Service::isAdmin();
		
		if(Service::isAuth()){
			$common['user-id'] = $_SESSION['user']['id'];
			$common['user-name'] = $_SESSION['user']['name'];
			$common['user-surname'] = $_SESSION['user']['surname'];
			
			$user_ava = "{$ds}upl{$ds}users{$ds}{$common['user-id']}{$ds}ava.jpg";
			$common['user-ava'] = is_file(WWW . $user_ava) ? $user_ava : '/img/interface/unknown_user.jpg';
		}
		
		if(isset($_SESSION['errors'])){
			$common['errors'] = '<div id="emergency" class="errors"><h2>Ошибка!</h2>';
			$common['errors'] .= $_SESSION['errors'];
			$common['errors'] .= '<a href="#" class="okbut bright_but">ОК</a></div>';
			unset($_SESSION['errors']);
		}
		if(isset($_SESSION['success'])){
			$common['success'] = '<div id="emergency" class="success">';
			$common['success'] .= $_SESSION['success'];
			$common['success'] .= '<a href="#" class="okbut bright_but">ОК</a></div>';
			unset($_SESSION['success']);
		}
		if(DEBUG){
			$common['rblogs'] = Service::rbLogs();
		}
		App::$reg->setProperty('common', $common);
    }
    
    public static function cacheCategory(){
        $cache = Cache::instance();
        $cats = $cache->get('cats');
        if(!$cats){
            $cats = \R::getAll("SELECT * FROM acat");
            $cache->set('cats', $cats);
        }
        return $cats;
    }
    
}