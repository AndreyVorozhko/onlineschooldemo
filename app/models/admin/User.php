<?php

namespace app\models\admin;

use app\service\Service;

class User extends AdminModel
{
    
    public $attributes = [
		'uid' => NULL,
		'name' => NULL,
		'surname' => NULL,
		'email' => NULL,
		'tel' => NULL,
		'whats' => NULL,
		'viber' => NULL,
		'teleg' => NULL,
		'pass' => NULL,
		'newpass' => NULL,
		'hava' => NULL,
		'about' => NULL,
		'notes' => NULL,
		'banned' => NULL,
		'activated' => NULL,
		'new' => NULL
	];
	
	public function save(string $tablename){
		if($this->attributes['uid'] === NULL){
			$table = \R::dispense($tablename);
			$table->role = \R::load('role', 2);
		}else{
			$table = \R::load($tablename, $this->attributes['uid']);
		}
		foreach($this->attributes as $name => $value){

			if($name == 'whats' || $name == 'viber' || $name == 'teleg' || $name == 'banned' || $name == 'activated' || $name == 'new'){
				$value = ($value == 'yes') ? 1 : NULL;
				$table->$name = $value;
			}
			
			if($value !== NULL){
				switch ($name){
					case 'uid':
						break;
						
					case 'hava':
						break;
						
					case 'pass':
						$table->$name = password_hash($value, PASSWORD_DEFAULT);
						break;
					case 'pass2':
						break;
					
					case 'newpass':
						$table->pass = password_hash($value, PASSWORD_DEFAULT);
						break;
						
					case 'email':
						// В этом нет смысла здесь, но код пригодится в аналогичном общем контроллере
						/*
						if(isset($table->email) && $table->email != $value && $table->activated == 1){
							//Делать акк не активированным и отправлять письмо об активации если почту меняет не админ
							if(!Service::isAdmin()){
								$table->activated = NULL;
								Service::reactivation($table->id, $_SERVER['REMOTE_ADDR'], $table->email);
							}
						}
						*/
						$table->$name = $value;
						break;
					default:
						$table->$name = $value;
				}
			}
		}
		$user_id = \R::store($table);
		
		$hava = $this->attributes['hava'];
		if($hava != NULL){
			$path = WWW . DS . 'upl' . DS . 'users' . DS . $user_id . DS . 'ava.jpg';
			$pinfo = pathinfo($path);
			if(!is_dir($pinfo['dirname'])){
				mkdir($pinfo['dirname'], 0755, true);
			}

			$image = new \claviska\SimpleImage();
			$image->fromDataUri($hava);
			$w = $image->getWidth();
			if($w > 240){
				$image->resize(240, NULL);
			}
			$image->toFile($path, 'image/jpeg');

		}
	}
}