<?php

namespace app\models;

use vorozhok\base\Model;

class AppModel extends Model
{
    
    public function checkHoney(){
		$cap = $this->attributes['captcha'];
		$addr = $this->attributes['addr'];
		if($cap == '' && preg_match('%^\d*-\d*$%',$addr)){
			return true;
		}else{
			ob_start();
				var_dump($cap);
			$cap = ob_get_clean();
			ob_start();
				var_dump($addr);
			$addr = ob_get_clean();
			$this->errors['capcha'] = ["Вы используете очень старый браузер<br>Код ошибки: $cap|$addr"];
			return false;
		}
	}
    
}