<?php

namespace app\models;

use vorozhok\base\Model;
use Valitron\Validator;
use avadim\Qevix\Qevix;

class User extends AppModel
{
    // из форм в модель мы подгружаем только эти данные, это исключает загрузку левых данных
    public $attributes = [
		'name' => NULL,
		'email' => NULL,
		'pass' => NULL,
		'pass2' => NULL,
		'terms' => NULL,
		'captcha' => NULL,
		'addr' => NULL
	];
	
	public function saveProfile($data){
		$user = \R::load('user', $data['id']);
		if(!password_verify($data['curpass'], $user->pass)){
			$_SESSION['errors'] = 'Веден неверный текущий пароль. Изменения не приняты системой!';
			redirect();
			//exit();
		}
		
		if(!empty($data['myhidden'])){
			$img = str_replace('data:image/jpeg;base64,', '', $data['myhidden']);
			$img = str_replace(' ', '+', $img);
			$dataImg = base64_decode($img);
			$ds = DS;
			$avaPath = WWW . "{$ds}upl{$ds}users{$ds}{$user->id}{$ds}ava.jpg";
			if(!is_dir(pathinfo($avaPath, PATHINFO_DIRNAME))){
				mkdir(pathinfo($avaPath, PATHINFO_DIRNAME));
			}
			if(!file_put_contents($avaPath, $dataImg)){
				$_SESSION['errors'] = 'Не удалось сохранить аватарку!';
			}
		}
		
		$rules = [
			'email' => [
				['email']
			],
			'lengthMin' => [
				['newpass', 6]
			],
			'equals' => [
				['newpass','newpass2']
			],
			'different' => [
				['name','newpass'],['email','newpass'],['curpass','newpass']
			]
		];
		
		
		Validator::langDir(WWW.'/assets/validator/lang'); // always set langDir before lang.
		Validator::lang('ru');
		$v = new Validator($data);
		$v->rules($rules);
		
		if(!$v->validate()){
			$this->errors = $v->errors();
			$_SESSION['errors'] = $this->getErrors();
			redirect();
		}
		
		//Фильтруем ненужные теги в описании юзера
		$qevix = new Qevix(CONF . DS . 'quevix.php');
		$about = $qevix->parse($data['about'], $errors);
		
		$user->about = $about;
		
		if(!empty(trim($data['name']))){
			$user->name = $data['name'];
		}
		if(!empty(trim($data['surname']))){
			$user->surname = $data['surname'];
		}
		if(!empty(trim($data['email']))){
			$user->email = $data['email'];
		}
		if(!empty(trim($data['tel']))){
			$user->tel = $data['tel'];
		}
		if(!empty(trim($data['newpass']))){
			$user->pass = password_hash($data['newpass'], PASSWORD_DEFAULT);
		}
		
		$user->whats = $data['whats'] ?? NULL;
		$user->viber = $data['viber'] ?? NULL;
		$user->teleg = $data['teleg'] ?? NULL;
		
		// Заполняем дедлайны если надо
		foreach($data as $field => $value){
			if(preg_match('%^id\d+%', $field) && !empty(trim($value))){
				$cid = preg_replace("/[^0-9]/", '', $field);
				$access = \R::findOne('access', 'course_id = ? AND user_id = ?', [$cid, $user->id]);
				if(!isset($access->deadline)){
					$access->deadline = strtotime($value);
					\R::store($access);
				}
			}
		}
		
		if(\R::store($user)){
			$_SESSION['success'] = 'Все изменения успешно сохранены!';
		}else{
			$_SESSION['errors'] = 'Не удалось сохранить изменения!';
		}
		redirect();
	}
    
}