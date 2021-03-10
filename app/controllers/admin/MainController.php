<?php

namespace app\controllers\admin;

use app\service\Service;

class MainController extends AdminController
{
	
	public function indexAction(){
		$this->setMeta('Панель управления', 'Закрытая зона администратора', 'Админпанель');
		
		if(!Service::isAdmin()){
			$this->view = 'login';
		}else{
			$this->view = 'index';
		}
		
	}
	
	public function adminLoginAction(){
		debug($_POST);
		if(!empty($_POST)){
			if(!Service::checkHoney($_POST)){
				redirect();
			}else{
				//не забыть перенаправить в кабинет
				$login = secureStr($_POST['login']);
				$pass = $_POST['pass'];
				$user = \R::findOne('user','email = ?', [$login]);
				if($user === NULL || !password_verify($pass, $user->pass)){
					$_SESSION['errors'] = "<ul><li>Неверное имя пользователя / email или пароль</li></ul>";
					redirect();
				}else{
					$_SESSION['user']['name'] = $user->name;
					$_SESSION['user']['surname'] = $user->surname;
					$_SESSION['user']['id'] = $user->id;
					$_SESSION['user']['email'] = $user->email;
					$_SESSION['user']['role'] = $user->role->name;
					
					if($user->role->name == 'admin'){
						redirect(PATH . ADMINNAME);
					}else{
						$_SESSION['errors'] = "<ul><li>Здесь вход только для админов! Другие юзеры не могут тут войти.</li></ul>";
						redirect();
					}
				}
			}
		}
	}
	
}