<?php

namespace app\controllers;

use vorozhok\App;
use vorozhok\Cache;
use vorozhok\StdData;
use app\models\Rates;

class PhotographerController extends AppController
{	
	
    public function indexAction(){
        $this->setMeta(App::$reg->getProperty('site_name'),'Обучение фотографов маркетингу и продажам, а так же создание продающих сайтов для фотографов','сайты для фотографа');
		$data = new StdData();
		$data->setProp('if_index', true);
        
        $rates = new Rates;
        $data->setProp('rates', $rates->getAllRates(1)); // 1 - это номер проекта
		$this->set($data);
    }

}