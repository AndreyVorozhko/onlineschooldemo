<?php

namespace app\models;

use vorozhok\base\Model;

class Rates extends AppModel
{
    // Получим нужные данные по всем тарифам курса на продакшене проекта (поэтому передаем pid - project_id)
    public function getAllRates($pid){
        $productionCourse = \R::findOne('course', 'project_id = ? AND status_id = ?', [$pid, 2]);
        $rates = $productionCourse->ownRateList;
        
        $info = [];
        
        foreach($rates as $rate){
            switch ($rate->id){
                
                case 1:
                    $name = 'self';
                    break;
                case 2:
                    $name = 'team';
                    break;
                case 3:
                    $name = 'premium';
                    break;
                default:
                    throw new \Exception('There is no such reate in database!', 404);
            }
            $profit = $rate->price - $rate->promoprice;
            $discount = $profit / $rate->price * 100;
            $busy = \R::count('access', 'rate_id = ? AND finished = ?', [$rate->id, 0]);
            $places = $rate->lim - $busy;
            $action = ($rate->promodate > time()) ? 1 : 0;
            $actionDays = floor(($rate->promodate - time())/86400);
            $avail = ($places > 0 && $rate->avail == 1) ? true : false;
            $info[$name] = ['name' => $rate->name, 'price' => $rate->price, 'promodate' => $rate->promodate, 'promoprice' => $rate->promoprice, 'avail' => $avail, 'places' => $places, 'profit' => $profit, 'discount' => $discount, 'action' => $action, 'action_days' => $actionDays];
        }
        
		return $info;
	}
    
}