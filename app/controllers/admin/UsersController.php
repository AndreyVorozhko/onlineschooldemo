<?php

namespace app\controllers\admin;

use vorozhok\App;
use vorozhok\StdData;
use app\models\admin\User;
use app\models\admin\Access;
use app\service\Service;
use vorozhok\libs\AdminPagination;

class UsersController extends AdminController
{
	
	public function indexAction(){
		$route = $this->route;
		$this->allowQueries(true);
		
		$this->setMeta('Раздел работы с пользователями', 'Просмотр, добавление и редактирование пользователей', 'пользователь');
		
		$page = isset($route['query']['page']) ? $route['query']['page'] : 1;
		$perpage = App::$reg->getProperty('admin_pagination');
		$total = \R::count('user', "role_id=2");
		
		$pagination = new AdminPagination($page, $perpage, $total);
		$start = $pagination->getStart();
		
		$users = \R::getAll("SELECT id, name, email, surname, tel, whats, viber, teleg, banned, activated FROM user WHERE role_id=? ORDER BY id DESC LIMIT $start, $perpage", [2]);
		$data = new StdData();
		$bread = [
			//['link' => '/cources', 'title' => 'Управление курсами']
		];
		$data->setProp('user_list', $users);
		
		$admins = \R::getAll("SELECT id, name, email, surname, tel, whats, viber, teleg, banned, activated FROM user WHERE role_id=? ORDER BY id DESC", [1]);
		$data->setProp('admin_list', $admins);
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Управление пользователями', 'pagination' => $pagination]);
		$this->set($data);
	}
	
	public function editAction(){
		$route = $this->route;
		$this->allowQueries(true);
		
		if(!isset($_POST['submit'])){
			$this->setMeta('Редактировать информацию о пользователе', 'Изменить информацию о пользователе', 'пользователь');
			$user = \R::load('user', $route['query']);
			
			$data = new StdData();
			
			$bread = [
				['link' => 'users', 'title' => 'Управление пользователями']
			];
			$data->setProps([
				'bread' => $bread,
				'title_not_active' => 'Редатировать пользователя',
				
				'uid' => $user->id,
				'name' => $user->name,
				'email' => $user->email
				
			]);
			$this->set($data);
		}else{
			$this->editUser();
		}
	}
	
	private function editUser(){
		$route = $this->route;
		$this->allowQueries(true);
		
		$data = $_POST;
		$data['uid'] = $route['query'];
		
		if(isset($data['newpass']) && $data['newpass'] != $data['newpass2']){
			$_SESSION['errors'] = "Ошибка: Введенные пароли не совпадают!";
			redirect();
		}
		
		$user = new User;
		$user->load($data);
		
		try{
			$user->save('user');
			$_SESSION['success'] = "Измененные данные пользователя \"{$data['name']} {$data['surname']}\" успешно сохранены!";
		}catch(\Exception $e){
			$_SESSION['errors'] = "Не удалось сохранить данные пользователя \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
		}finally{
			redirect(ADMIN . '/users');
		}
	}
	
	public function addAction(){
		if(!isset($_POST['submit'])){
			$this->setMeta('Добавить нового пользователя на сайт', 'Добавить нового пользователя в систему через админку', 'пользователь');
			
			$data = new StdData();
			
			$bread = [
				['link' => 'users', 'title' => 'Управление пользователями']
			];
			$data->setProps([
				'bread' => $bread,
				'title_not_active' => 'Добавить нового'
			]);
			$this->set($data);
		}else{
			$this->addUser();
		}
	}
	
	private function addUser(){
		$data = $_POST;
		
		if($data['pass'] != $data['pass2']){
			$_SESSION['errors'] = "Ошибка: Введенные пароли не совпадают!";
			redirect();
		}
		
		$user = new User;
		$user->load($data);
		
		try{
			$user->save('user');
			$_SESSION['success'] = "Пользователь \"{$data['name']} {$data['surname']}\" успешно добавлен в систему!";
		}catch(\Exception $e){
			$_SESSION['errors'] = "Не удалось добавить пользователя \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
		}finally{
			redirect(ADMIN . '/users');
		}
	}
	
	public function viewAction(){
		$this->allowQueries(true);
		$route = $this->route;
		
		$this->setMeta('Просмотр профиля пользователя', 'Посмотреть подробную информацию о пользователе', 'пользователь');
		$user = \R::load('user', $route['query']);
		
		$info = $user->getProperties();
		$info_list = [];
		foreach($info as $k => $v){
			switch($k){
				case 'id':
					$info_list['uid'] = $v;
				break;
				case 'role_id':
					$info_list['role'] = $user->role->rus;
				break;
				
				case 'whats':
				case 'viber':
				case 'teleg':
				case 'banned':
				case 'activated':
				case 'new':
					$info_list[$k] = ($v == 1) ? '<span class="text-success">Да</span>' : '<span class="text-danger">Нет</span>';
				break;
				default:
				$info_list[$k] = $v;
			}
		}
		
		$access_list = \R::getAll('
		
			SELECT a.id, a.course_id AS vid, proj.name AS project, v.version, s.name AS status, r.name AS rate, r.price AS price
			FROM access AS a
			INNER JOIN course AS v ON v.id = a.course_id
			INNER JOIN project AS proj ON proj.id = v.project_id
			INNER JOIN rate AS r ON r.id = a.rate_id
			INNER JOIN status AS s ON s.id = v.status_id
			WHERE a.user_id=?
			
		', [$route['query']]);
		
		$user->new = NULL;
		\R::store($user);
		
		$data = new StdData();
		
		$bread = [
			['link' => 'users', 'title' => 'Управление пользователями']
		];
		$data->setProps([
			'bread' => $bread,
			'title_not_active' => 'Просмотр пользователя',
			
			'info_list' => $info_list,
			'access_list' => $access_list
			
		]);
		$this->set($data);
	}
	
	public function userDeleteAction(){
		$this->allowQueries(true);
		$route = $this->route;
		
		if(Service::isAdmin()){
			$user = \R::load('user', $route['query']);
			Service::rRmDir(WWW . "/upl/users/{$route['query']}");
			try{
				\R::trash($user);
				$_SESSION['success'] = "Пользователь \"{$user->name} {$user->surname}\" успешно удален!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось удалить пользователя \"{$user->name} {$user->surname}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect();
			}
		}
	}
	
	public function addAccessAction(){
		$this->allowQueries(true);
		$route = $this->route;
		
		if(!isset($_POST['submit'])){
			$this->setMeta('Дать пользователю доступ к курсу', 'Допустить юзера к курсу', 'пользователь');
			$project_list = \R::getAll('SELECT id, name FROM project');
			$curator_list = \R::getAll('SELECT cur.id, cur.name, role.rus AS role FROM user AS cur INNER JOIN role ON role.id = cur.role_id WHERE cur.role_id != ?', [2]);
			
			$data = new StdData();
			
			$bread = [
				['link' => 'users', 'title' => 'Управление пользователями'],
				['link' => 'users/view', 'title' => 'Просмотр пользователя']
			];
			$data->setProps([
				'bread' => $bread,
				'title_not_active' => 'Предоставить доступ к курсу',
				
				'project_list' => $project_list,
				'curator_list' => $curator_list
				
			]);
			$this->set($data);
		}else{
			$this->allowQueries(true);
			$route = $this->route;
            
			$data = $_POST;
			$data['user'] = $route['query'];
		
			$access = new Access();
			$access->load($data);
			
			$user = \R::getRow('SELECT name, surname FROM user WHERE id=? LIMIT 1', [$data['user']]);
			$course = \R::getCell('SELECT name FROM project WHERE id=? LIMIT 1', [$data['course']]);
			
			try{
				$access->saveAccess();
				$_SESSION['success'] = "Доступ к курсу \"$course\" для пользователя \"{$user['name']} {$user['surname']}\" успешно предоставлен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось предоставить доступ к курсу \"$course\" для пользователя \"{$user['name']} {$user['surname']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect(ADMIN . "/users/view/{$route['query']}");
			}
		}
	}
	
	public function getRatesAction(){
		//Эта страница нужна для зависимых списков проект->тариф (для ответа по AJAX)
		$this->allowQueries(true);
		$route = $this->route;

		if(Service::isAdmin()){
			$rates_list = \R::getAll('SELECT r.id, r.name, r.price FROM rate AS r INNER JOIN course AS c ON r.course_id = c.id WHERE c.project_id=?', [$route['query']]);
			$data = new StdData();
			$data->setProp('rates_list', $rates_list);
			$this->set($data);
		}
	}
	
	public function getVersionsAction(){
		//Эта страница нужна для зависимых списков проект->версия курса (для ответа по AJAX)
		$this->allowQueries(true);
		$route = $this->route;
		if(Service::isAdmin()){
			$version_list = \R::getAll('SELECT c.id, c.version, s.name AS status FROM course AS c INNER JOIN status AS s ON c.status_id = s.id WHERE project_id=?', [$route['query']]);
			
			$data = new StdData();
			$data->setProp('version_list', $version_list);
			$this->set($data);
		}
	}
}