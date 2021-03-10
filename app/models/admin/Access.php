<?php

namespace app\models\admin;

use vorozhok\base\Model;

class Access extends AdminModel
{
    // из форм в модель мы подгружаем только эти данные, это исключает загрузку левых данных
    public $attributes = [
		'user' => NULL,
		'rate' => NULL,
		'curator' => NULL,
		'sum' => NULL,
		'dop' => NULL,
		'upfrom' => NULL,
		'com' => NULL,
		'payment' => NULL,
		'paydate' => NULL,
		'deadline' => NULL,
		'course' => NULL,
		'new' => NULL
	];
    
	public function saveAccess(){
		$access = \R::dispense('access');
		$access->date = time();
		foreach($this->attributes as $name => $value){
			if($value !== NULL){
				switch($name){
					
					case 'rate':
					case 'user':
						$access->$name = \R::load($name, $value);
					break;
					
					case 'course':
						$access->course = \R::load('course', $value);
					break;
					
					case 'curator':
						$curator = \R::load('user', $value);
						if($curator->role->name != 'user'){
							$access->curator = $curator;
						}
					break;
					
					case 'dop':
					case 'upfrom':
					case 'sum':
					case 'com':
					case 'paydate':
					break;
					
					case 'payment':
						$payment = \R::dispense('payment');
						$payment->sum = $this->attributes['sum'];
						if($this->attributes['dop'] == 'yes'){
							$payment->upfrom = \R::load('rate', $this->attributes['upfrom']);
						}
						$payment->date = strtotime($this->attributes['paydate']);
						$payment->com = $this->attributes['com'];
						$access->payment = $payment;
						\R::store($payment);
					break;
					
					default:
					$access->$name = $value;
				}
			}
		}
		\R::store($access);
	}
}