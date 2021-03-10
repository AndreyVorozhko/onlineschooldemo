<?php

namespace app\models\admin;

use vorozhok\Cache;
use claviska\SimpleImage;

class Article extends AdminModel
{
    
    public $attributes = [
		'aid' => NULL,
		'title' => '',
		'seotitle' => '',
		'seodesc' => '',
		'seokeyw' => '',
		'alias' => '',
		'pubdate' => NULL,
		'lastmod' => NULL,
		'category' => '',
		'aimg' => NULL,
		'myhidden' => NULL,
		'html' => ''
	];
	
	private function genSizes(string $source, array $desired){
		$spinfo = pathinfo($source);
		$original = str_replace('_hd_po', '', $spinfo['filename']);
		foreach($desired as $img){
			try{
				$im = new SimpleImage();
				
				$im->fromFile($source)
				->resize($img['width'],$img['height'])
				->toFile("{$spinfo['dirname']}/{$original}{$img['suffix']}.jpg", 'image/jpeg')
				->toFile("{$spinfo['dirname']}/{$original}{$img['suffix']}.webp", 'image/webp', 85);
			}catch(Exception $err) {
			  echo "Error: {$err->getMessage()}";
			}
		}
		
	}
	
	public function save(string $tablename){
		if($this->attributes['aid'] === NULL){
			$table = \R::dispense($tablename);
		}else{
			$table = \R::load($tablename, $this->attributes['aid']);
			$this->attributes['lastmod'] = time();
		}
		
		if(isset($_FILES) && $_FILES['aimg']['error'] == 0){ // Проверяем, загрузил ли пользователь файл
		
			$cache = Cache::instance();
			$imgdir = $cache->get('img_path');
			
			$destiation = WWW . DS . "$imgdir/{$_FILES['aimg']['name']}"; // Новый путь файла вместе с самим файлом
			move_uploaded_file($_FILES['aimg']['tmp_name'], $destiation ); // Перемещаем файл в желаемую директорию
			
			$pinfo = pathinfo($destiation);
			try{
				$pict = new SimpleImage();
				
				$pict->fromFile($destiation)
				->toFile("{$pinfo['dirname']}/{$pinfo['filename']}.webp", 'image/webp', 85);
			}catch(Exception $err) {
			  echo "Error: {$err->getMessage()}";
			}
			
			$desired_1 = [
				[
					'suffix' => '_hd_la',
					'width' => 1280,
					'height' => null
				],
				[
					'suffix' => '_mob_la',
					'width' => 853,
					'height' => null
				]
			];
			
			$this->genSizes($destiation,$desired_1);
		}else{
			$this->attributes['aimg'] = NULL;
		}
		foreach($this->attributes as $name => $value){
			switch ($name){
				case 'aimg':
					if($value !== NULL){
						$table->$name = $value;
					}
					break;
				case 'aid':
					break;
				case 'pubdate':
					if($value != ""){
						$value = strtotime($value);
						$table->$name = $value;
					}else{
						$value = time();//now in unix
					}
					$table->$name = $value;
					break;
				case 'category':
					$cat = \R::load('acat', $value);
					$table->acat = $cat;
					break;
				case 'myhidden':
					if($value != NULL){
						if(!isset($pinfo)){
							$pinfo = pathinfo(WWW . DS . 'upl' . DS . 'articles' . DS . $this->attributes['aid'] . DS . $table->aimg . '.jpg');
							//file_put_contents('test.txt', $pinfo);
						}
						$value = str_replace('data:image/jpeg;base64,', '', $value);
						$value = str_replace(' ', '+', $value);
						$data = base64_decode($value);
						$portrait = "{$pinfo['dirname']}/{$pinfo['filename']}_hd_po.jpg";
						file_put_contents($portrait, $data);
						
						try{
							$pict = new SimpleImage();
							
							$pict->fromFile($portrait)
							->toFile("{$pinfo['dirname']}/{$pinfo['filename']}_hd_po.webp", 'image/webp', 85);
						}catch(Exception $err) {
						  echo "Error: {$err->getMessage()}";
						}
						
						$desired_2 = [
							[
								'suffix' => '_mob_po',
								'width' => 480,
								'height' => null
							]
						];
						$this->genSizes($portrait,$desired_2);
					}
					
					break;
				case 'html':
					$value = preg_replace_callback(
						'%<img.*src="(.+[.].*(?:jpg|png|gif)).*alt="(.*)">%U',
						function ($matches) {
							//print_r($matches);
							$spinfo = pathinfo($matches[1]);
							$alt = $matches[2];
							return "<picture><source media=\"(min-width: 1281px)\" srcset=\"{$spinfo['dirname']}/{$spinfo['filename']}.webp\"><source media=\"(min-width: 481px)\" srcset=\"{$spinfo['dirname']}/{$spinfo['filename']}_hd_la.webp, {$spinfo['dirname']}/{$spinfo['filename']}.webp 2x\"><source media=\"(min-width: 1px)\" srcset=\"{$spinfo['dirname']}/{$spinfo['filename']}_mob_la.webp, {$spinfo['dirname']}/{$spinfo['filename']}_hd_la.webp 2x\"><img src=\"{$matches[1]}\" srcset=\"{$matches[1]}\" alt=\"$alt\"></picture>";
						},
						$value
					);
					$value = str_replace(PATH, '', $value);
					$table->$name = $value;
					break;
				default:
					$table->$name = $value;
				}
			}
		
		// we need to add name of the file to DB
		$table->aimg = $pinfo['filename'] ?? "";
		
		if(\R::store($table)){
			return true;
		}else{
			throw new \Exception("Не удалось сохранить статью!");
		}
	}

}