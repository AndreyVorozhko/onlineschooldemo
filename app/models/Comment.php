<?php

namespace app\models;

use vorozhok\base\Model;

class Comment extends AppModel
{
    // из форм в модель мы подгружаем только эти данные, это исключает загрузку левых данных
    public $attributes = [
		'yourname' => NULL,
		'artid' => '',
		'yourmail' => NULL,
		'commtext' => '',
		'captcha' => '',
		'addr' => '',
		'uid' => NULL
	];
	
	public function save(string $tablename){
		$table = \R::dispense($tablename);
		foreach($this->attributes as $name => $value){
			
			if($value === NULL){
				continue;
			}
			
			switch($name){
				case 'artid':
					$art = \R::load('article', $value);
					$table->article = $art;
					break;
				case 'uid':
					$user = \R::load('user', $value);
					$table->user = $user;
					break;
				case 'commtext':
					$value = strip_tags(nl2br($value), '<br>');
					$table->$name = $value;
					break;
				default:
					$table->$name = $value;
			}
		}
		$table->comdate = time();
		$table->accepted = 0;
		\R::store($table);
	}

}