<?php

namespace vorozhok\base;

use vorozhok\Db;
use Valitron\Validator;

abstract class Model
{
    //Свойство для хранения массива свойств модели, идентичных полям базы данных, это нужно чтобы автоматически выгружать из форм данные и сохранять их в базу данных
    public $attributes = [];
    //Свойство для хранения ошибок
    public $errors = [];
    //Свойство для правил валидации данных
    public $rules = [];
    
    public function __construct(){
        //В конструкторе мы организуем подключение к базе данных
        Db::instance();
    }
	
	public function load($data){
		foreach($this->attributes as $name => $value){
			if(isset($data[$name]) && $data[$name] !== ''){
				$this->attributes[$name] = $data[$name];
			}
		}
	}
	
	public function setRules(array $rules){
		$this->rules = $rules;
	}
	
	public function validate(){
		Validator::langDir(WWW.'/assets/validator/lang'); // always set langDir before lang.
		Validator::lang('ru');
		$v = new Validator($this->attributes);
		$v->rules($this->rules);
		if($v->validate()){
			return true;
		}else{
			$this->errors = $v->errors();
			return false;
		}
	}
	
	public function checkDouble(){
		if(\R::findOne('user', 'email = ?', [$this->attributes['email']]) == NULL){
			return true;
		}else{
			$this->errors[] = ['Пользователь с такой электронной почтой уже зарегистрирован'];
			return false;
		}
	}
	
	public function save(string $tablename){
		$table = \R::dispense($tablename);
		foreach($this->attributes as $name => $value){
			$table->$name = $value;
		}
		\R::store($table);
	}
	
	public function getErrors(){
		$errors = '<ul>';
		foreach($this->errors as $error){
			foreach($error as $e){
				$errors .= "<li>$e</li>";
			}
		}
		$errors .= '</ul>';
		return $errors;
	}
}