<?php

namespace app\controllers\admin;

use ext\albakov\Handler;
use vorozhok\StdData;
use app\models\admin\Article;
use vorozhok\Cache;
use claviska\SimpleImage;
use app\service\Service;

class ArticlesController extends AdminController
{
	
	public function indexAction(){
		$this->setMeta('Статьи на сайте', 'Просмотр, добавление и редактирование статей на сайте', 'статьи');
		$arts = \R::getAll('SELECT a.id, a.title, a.alias, FROM_UNIXTIME(a.pubdate, \'%d-%m-%Y %H:%i\') AS pdate, a.pubdate, acat.title AS cat, acat.alias AS catalias FROM article AS a INNER JOIN acat ON acat.id = a.acat_id');
		$cats = \R::getAll('SELECT id, title, alias FROM acat');
		$data = new StdData();
		$data->setProp('art_list', $arts);
		$data->setProp('cat_list', $cats);
		$bread = [
			//['link' => '/articles', 'title' => 'Управление статьями']
		];
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Управление статьями']);
		$this->set($data);
	}
	
	private function setImgPath(){
		$last = \R::findLast('article');
		if($last === null){
			$newid = 1;
		}else{
			$newid = $last->id + 1;
		}
		$adir = "upl/articles/$newid";
		
		$cache = Cache::instance();
		$cache->set('img_path', $adir, 86400);
		
		if(!is_dir(WWW . DS . $adir)){
			mkdir(WWW . DS . $adir);
		}
	}
	
	public function addAction(){
		
		if(!isset($_POST['submit'])){
			$this->setMeta('Добавить новую статью на сайт', 'Добавление и редактирование статей на сайте', 'статьи');
			//debug(\vorozhok\Router::getRoute());
			
			$this->setImgPath();
			
			$catsa = \R::getAssoc('SELECT id, title FROM acat');
			$cats = [];
			foreach($catsa as $id=>$title){
				$cats[] = ['cat_id' => $id, 'cat_title' => $title];
			}

			$data = new StdData();
			$data->setProp('cats', $cats);
			$bread = [
				['link' => '/' . ADMINNAME . '/articles', 'title' => 'Управление статьями']
			];
			$data->setProps(['bread' => $bread, 'title_not_active' => 'Добавить новую']);
			$this->set($data);
		
		}else{
			$data = $_POST;
			$article = new Article();
			$article->load($data);
			
			try{
				$article->save('article');
				Service::genSitemap();
				$_SESSION['success'] = "Статья \"{$data['title']}\" успешно добавлена!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось добавить статью \"{$data['title']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect();
			}
		}
		
	}
	
	public function editAction(){
		$this->allowQueries(true);
		if(!isset($_POST['submit'])){
		$this->setMeta('Редактировать статью', 'Редактирование статьи на сайте', 'статьи');
		$route = \vorozhok\Router::getRoute();
		
		$adir = "upl/articles/{$route['query']['aid']}";
		$cache = Cache::instance();
		$cache->set('img_path', $adir, 86400);
		
		$catsa = \R::getAssoc('SELECT id, title FROM acat');
		$cats = [];		
		
		$art = \R::getRow("SELECT *, FROM_UNIXTIME(pubdate, '%Y,%m-1,%d,%H,%i,%s') AS pubdate FROM article WHERE id=? LIMIT 1", [$route['query']['aid']]);
		foreach($catsa as $id=>$title){
			$selected = false;
			if($id == $art['acat_id']){
				$selected = true;
			}
			$cats[] = ['cat_id' => $id, 'cat_title' => $title, 'if_selected' => $selected];
		}
		$data = new StdData();
		$bread = [
			['link' => '/' . ADMINNAME . '/articles', 'title' => 'Управление статьями']
		];
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Редактирование статьи']);
		$data->setProp('cats', $cats);
		$index = PATH;
		foreach($art as $k=>$v){
			switch($k){
				case 'html':
					$v = preg_replace_callback("%(?:src=\")|(?:srcset=\")|(?:.webp,\s)%",function($matches) use ($index){
						return "{$matches[0]}$index";
					},$v);
				break;
				case 'aimg':
					$v = "$index$adir/$v.jpg";
				break;
			}
			$data->setProp($k, $v);
		}
		$this->set($data);
		}else{
			$data = $_POST;
			$article = new Article();
			$article->load($data);
			
			try{
				$article->save('article');
				Service::genSitemap();
				$_SESSION['success'] = "Статья \"{$data['title']}\" успешно изменена!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось изменить статью \"{$data['title']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect();
			}
		}
	}
	
	public function deleteAction(){
		$this->allowQueries(true);
		$route = \vorozhok\Router::getRoute();
		
		if(Service::isAdmin()){
			try{
				\R::hunt('article', 'id=?', [$route['query']['aid']]);
				Service::genSitemap();
				// recursive delete directory with article uploads
				Service::rRmDir(WWW . DS . 'upl' . DS . 'articles' . DS . $route['query']['aid']);
				$_SESSION['success'] = "Статья с id \"{$route['query']['aid']}\" успешно удалена!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось удалить статью с id \"{$route['query']['aid']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect();
			}
		}
	}
	
	public function addcatAction(){
		$this->setMeta('Добавить новую статью на сайт', 'Добавление и редактирование статей на сайте', 'статьи');
		//debug(\vorozhok\Router::getRoute());
		if(isset($_POST['submit'])){
			$title = secureStr($_POST['title']);
			$alias = secureStr($_POST['alias']);
			if(\R::findOne('acat', 'title = ? OR alias = ?', [$title, $alias])){
				$_SESSION['errors'] = "Такая рубрика уже существует!";
				redirect();
			}
			$cat = \R::dispense('acat'); // articles category
			$cat->title = $title;
			$cat->alias = $alias;
			
			try{
				\R::store($cat);
				$_SESSION['success'] = "Рубрика \"$title\" успешно добавлена!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось добавить рубрику \"$title\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect();
			}
			
		}
	}
	
	public function uploadAction(){
		$cache = Cache::instance();
		$dir = $cache->get('img_path');
		
		$uploads_dir = WWW . DS . $dir;
		$success = false;
		if ($_FILES["image"]["error"] == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES['image']['tmp_name'];
			$name = basename($_FILES['image']['name']);
			$success = move_uploaded_file($tmp_name, "$uploads_dir/$name");
		}
		
		$source = "$uploads_dir/$name";
		$spinfo = pathinfo($source);
		
		try{
			$im = new SimpleImage();
			
			$im->fromFile($source)
			->toFile("{$spinfo['dirname']}/{$spinfo['filename']}.webp", 'image/webp', 85);
			
			$im->fromFile($source)
			->resize(1280,null)
			->toFile("{$spinfo['dirname']}/{$spinfo['filename']}_hd_la.webp", 'image/webp', 85);
			
			$im->fromFile($source)
			->resize(853,null)
			->toFile("{$spinfo['dirname']}/{$spinfo['filename']}_mob_la.webp", 'image/webp', 85);
		}catch(Exception $err) {
		  echo json_encode(['success' => 'fail', 'error' => "Error: {$err->getMessage()}"]);
		}
		
		echo json_encode(['success' => $success, 'url' => PATH . "$dir/$name"]);
		
	}
}