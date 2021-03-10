<?php

namespace app\models;

use vorozhok\base\Model;

class Answer extends AppModel
{
    // из форм в модель мы подгружаем только эти данные, это исключает загрузку левых данных
    public $attributes = [
		'content' => NULL,
		'date' => NULL,
		'accepted' => 0,
		'new' => 0,
		'lesson_id' => NULL,
		'user_id' => NULL,
		'curator_id' => NULL
	];
    
}