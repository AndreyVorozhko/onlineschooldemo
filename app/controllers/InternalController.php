<?php

namespace app\controllers;

use app\service\Service;

class InternalController extends AppController
{

    public function gensitemapAction(){
		if(!Service::isAdmin()){
			throw new \Exception('You have no access to this page!', 404);
		}
		
		Service::genSitemap();
		
	}

}