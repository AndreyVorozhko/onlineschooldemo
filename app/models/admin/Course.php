<?php

namespace app\models\admin;

class Course extends AdminModel
{
    
    public $attributes = [
		'name' => NULL,
		'status' => NULL,
		'project' => NULL,
		'version' => NULL,
		'creation' => NULL,
		'notes' => NULL,
		'changes' => NULL,
		'price' => NULL,
		'promodate' => NULL,
		'promoprice' => NULL,
		'cid' => NULL,
		'lid' => NULL,
		'rid' => NULL,
		'pid' => NULL,
		'beaf' => NULL,
		'insert' => NULL,
		'position' => NULL,
		'content' => NULL,
		'stop' => NULL,
		'bonus' => NULL,
		'rate' => NULL,
		'lim' => NULL,
		'avail' => NULL,
		'passed' => NULL,
		'accepted' => NULL,
		'new' => NULL,
		'byadmin' => NULL,
		'sum' => NULL,
		'upfrom' => NULL,
		'img' => NULL
	];
	
	public function save(string $tablename){
		if($this->attributes['cid'] === NULL){
			$table = \R::dispense($tablename);
		}else{
			$table = \R::load($tablename, $this->attributes['cid']);
		}
		$this->attributes['cid'] = NULL;
		foreach($this->attributes as $name => $value){
			if($value !== NULL){
				$table->$name = $value;
			}
		}
		\R::store($table);
	}
	
	public function saveProject(string $tablename){
		if($this->attributes['pid'] === NULL){
			$table = \R::dispense($tablename);
		}else{
			$table = \R::load($tablename, $this->attributes['pid']);
			$this->attributes['pid'] = NULL;
		}
		foreach($this->attributes as $name => $value){
			if($value !== NULL){
				$table->$name = $value;
			}
		}
		
		$table->img = $_FILES['img']['name'];
		$pid = \R::store($table);
		
		if(!empty($_FILES['img']['name'])){
			$ds = DS;
			$destination = WWW . DS . "upl{$ds}projects{$ds}$pid";
			if(!is_dir($destination)) mkdir($destination);
			$destination = "$destination{$ds}{$table->img}";
			$move = move_uploaded_file($_FILES['img']['tmp_name'],  $destination); // Перемещаем файл в желаемую директорию
		}
	}
	
	public function saveLesson(){
		$table = \R::dispense('lesson');
		foreach($this->attributes as $name => $value){
			if($value !== NULL){
				switch($name){
					
					case "beaf":
						break;
					case "insert":
						break;
					case "cid":
						$course = \R::load('course', $value);
						$table->course = $course;
						break;
					
					case "position":
						if($this->attributes['beaf'] == 'before'){
							// вставляемый принимает значение ориентира
							$table->$name = $this->attributes['insert'];
							// все что после ориентира +1 к номеру урока (position)
							\R::exec("UPDATE `lesson` SET `position` = `position` + 1 WHERE `position` >= {$this->attributes['insert']}");
						}elseif($this->attributes['beaf'] == 'after'){
							//вставляемый принимает значение (ориентир + 1)
							$table->$name = $this->attributes['insert'] + 1;
							// все уроки после ориентира + 1
							\R::exec("UPDATE `lesson` SET `position` = `position` + 1 WHERE `position` > {$this->attributes['insert']}");
						}else{
							$table->$name = $value;
						}
						break;
						
					case "stop":
						$value = ($value == "on") ? 1 : NULL;
						$table->$name = $value;
						break;
					
					case "bonus":
						$value = ($value == "on") ? 1 : NULL;
						$table->$name = $value;
						break;
					
					case "rate":
						if($value[0] != 'all'){
							foreach($value as $rate){
								$rate = \R::load('rate', $rate);
								$table->sharedRateList[] = $rate;
							}
						}else{
							$table->allrates = 1;
						}
						break;
					
					default:
						$table->$name = $value;
				}
			}
		}
		\R::store($table);
	}
	
	private function sortPosition(){
		$idpos = \R::getAssoc('SELECT position, id FROM lesson');
		
		ksort($idpos, SORT_NUMERIC);
		$counter = 0;
		foreach($idpos as $id){
			$counter++;
			\R::exec('UPDATE lesson SET position=? WHERE id=?', [$counter, $id]);
		}
	}
	
	public function editLesson(){
		$attr = $this->attributes;
		$table = \R::load('lesson', $attr['lid']);
		foreach($attr as $name => $value){
			
			switch($name){
				case "stop":
					$value = ($value == "on") ? 1 : NULL;
					$table->$name = $value;
				break;
				
				case "bonus":
					$value = ($value == "on") ? 1 : NULL;
					$table->$name = $value;
				break;
			}
			
			if($value !== NULL){
				switch($name){
					
					case "lid":
					break;
					
					case "beaf":
						break;
					case "insert":
						break;
					
					case "position":
						if($this->attributes['beaf'] == 'before'){
							if($this->attributes['insert'] != 1){
							// вставляемый принимает значение ориентира
							$table->$name = $this->attributes['insert'];
							// все что после ориентира +1 к номеру урока (position)
								\R::exec("UPDATE `lesson` SET `position` = `position` + 1 WHERE `position` >= {$this->attributes['insert']}");
							}else{
								\R::exec("UPDATE `lesson` SET `position` = `position` + 1");
								$table->$name = $this->attributes['insert'];
							}
						
						}elseif($this->attributes['beaf'] == 'after'){
							//вставляемый принимает значение (ориентир + 1)
							$table->$name = $this->attributes['insert'] + 1;
							// все уроки после ориентира + 1
							\R::exec("UPDATE `lesson` SET `position` = `position` + 1 WHERE `position` > {$this->attributes['insert']}");
						}else{
							$table->$name = $value;
						}
						break;
					
					case "rate":
						if($value[0] != 'all'){
							$table->sharedRateList = array();
							foreach($value as $rate){
								$rate = \R::load('rate', $rate);
								$table->sharedRateList[] = $rate;
							}
							$table->allrates = NULL;
						}else{
							$table->allrates = 1;
						}
					break;
					
					default:
						$table->$name = $value;
				}
			}
		}
		\R::store($table);
		if($this->attributes['beaf'] != 'pos'){
			$this->sortPosition();
		}
	}
	
	public function editRate(){
		$attr = $this->attributes;
		$table = \R::load('rate', $attr['rid']);
		foreach($attr as $name => $value){
			if($value !== NULL){
				switch($name){
					case "promodate":
						$value = strtotime($value);
						$table->$name = $value;
						break;
                    case "avail":
                        $table->$name = $value == "yes" ? 1 : 0;
						break;
					case "rid":
						break;
					default:
						$table->$name = $value;
				}
			}elseif($name == 'avail'){
                $table->$name = NULL;
            }
		}
		\R::store($table);
	}
	
	public function saveRate(){
		$table = \R::dispense('rate');
		
		foreach($this->attributes as $name => $value){
			if($value !== NULL){
				switch($name){
					
					case "cid":
						$course = \R::load("course", $value);
						$table->course = $course;
					break;
					
					case "promodate":
						$value = strtotime($value);
						$table->$name = $value;
						break;
					
					default:
						$table->$name = $value;
				}
			}
		}
		\R::store($table);
	}
}