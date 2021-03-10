<?php

namespace app\controllers;

use app\service\Service;
use vorozhok\StdData;
use avadim\Qevix\Qevix;
use app\models\Answer;

class AnswerController extends AppController
{
	
		public function addAction(){

		$qevix = new Qevix(CONF . DS . 'quevix.php');
		$text = $_POST['com'];
		$result = $qevix->parse($text, $errors);
		
		$answer = new Answer();
		$answer->load([
		
		'content' => $result,
		'date' => time(),
		'accepted' => 0,
		'new' => 1,
		'lesson_id' => $_POST['lid'],
		'user_id' => $_SESSION['user']['id'],
		'curator_id' => NULL
		
		]);
		
		try{
			$answer->save('answer');
			//$_SESSION['success'] = "Статья \"{$data['title']}\" успешно добавлена!";
		}catch(\Exception $e){
			//$_SESSION['errors'] = "Не удалось добавить статью \"{$data['title']}\"<br>Ошибка: {$e->getMessage()}";
		}finally{
			redirect();
		}
		
	}
	
}