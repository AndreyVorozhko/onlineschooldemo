<?php

namespace app\controllers;

use vorozhok\App;
use vorozhok\StdData;
use app\models\User;
use app\service\Service;
use vorozhok\Cache;


class UserController extends AppController
{	

	//Только для вывода формы
    public function addnewAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . " | Регистрация нового пользователя",'Регистрация нового пользователя на сайте','регистрация нового пользователя');
		$data = new StdData;
		$bread = [];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Регистрация нового пользователя']);
		$this->set($data);
	}
	
	//Для регистрации
	public function regAction(){
		if(!empty($_POST)){
			$user = new User();
			$data = $_POST;
			$user->load($data);
			$rules = [
				'required' => [
					['name'],['email'],['pass'],['pass2']
				],
				'email' => [
					['email']
				],
				'lengthMin' => [
					['pass', 6]
				],
				'equals' => [
					['pass','pass2']
				],
				'accepted' => [
					['terms']
				],
				'different' => [
					['name','pass'],['email','pass']
				]
			];
			$user->setRules($rules);
			if(!$user->validate() || !$user->checkHoney() || !$user->checkDouble()){
				if(!isset($_SESSION['errors'])){
					$_SESSION['errors'] = $user->getErrors();
				}
				redirect();
			}else{
				unset($user->attributes['pass2'],$user->attributes['terms'],$user->attributes['captcha'],$user->attributes['addr']);
				$user->attributes['pass'] = password_hash($user->attributes['pass'], PASSWORD_DEFAULT);
				$user->attributes['role'] = \R::findOne('role', 'name=?', ['user']);
				$user->save('user');
				$_SESSION['success'] = '<div><p>Вы успешно зарегистрировались в системе.</p><p>Добро пожаловать в ваш личный кабинет!</p></div>';
				redirect(PATH . 'user/area');
			}
		}
	}
	
	public function loginAction(){
		if(!empty($_POST)){
			if(!Service::checkHoney($_POST)){
				redirect();
			}else{
				//не забыть перенаправить в кабинет
				$login = secureStr($_POST['login']);
				$pass = $_POST['pass'];
				$user = \R::findOne('user','email = ?', [$login]);
				if(!$user && !password_verify($pass, $user->pass)){
					$_SESSION['errors'] = "<ul><li>Неверное имя пользователя / email или пароль</li></ul>";
					redirect();
				}else{
					$_SESSION['user']['name'] = $user->name;
					$_SESSION['user']['surname'] = $user->surname;
					$_SESSION['user']['id'] = $user->id;
					$_SESSION['user']['email'] = $user->email;
					$_SESSION['user']['role'] = $user->role->name;// имя роли юзера добываем из бина, с которым связь M:1
					
					if($user->role->name == 'admin'){
						redirect(PATH . ADMINNAME);
					}else{
						redirect(PATH . 'user/area');
					}
				}
			}
		}
	}
	
	public function logoutAction(){
		if(isset($_SESSION['user'])){
			unset($_SESSION['user']);
			redirect();
		}
	}
	
	//Кабинет юзера будет называться area
	public function areaAction(){
		$route = $this->route;
		$this->setMeta(App::$reg->getProperty('site_name') . " | Личный кабинет клиента",'Личный кабинет клиента','личный кабинет');
		
		// авторизован ли пользователь
		$this->checkAuth();
		
		$this->allowQueries(true);
		$this->layout = 'userarea';
		
		// защита от доступа к чужим кабинетам
		$this->checkOwner();
		
		//something new		
		$eventsCount = \R::count('answer', 'new = 1 AND user_id = ? AND curator_id IS NOT NULL', [$_SESSION['user']['id']]);
		
		$events = FALSE;
		if($eventsCount != 0){
			$events['count'] = $eventsCount;
		}
		if($events){
			$common = App::$reg->getProperty('common') ?? [];
			$common = array_merge($common, ['events' => $events]);
			App::$reg->setProperty('common', $common);
		}
		
		if(isset($route['query']['profile']) && $route['query']['profile'] == 'view'){
			$this->profile();
		}elseif(isset($route['query']['cid']) && !isset($route['query']['lid'])){
			$this->course();
		}elseif(isset($route['query']['cid']) && isset($route['query']['lid'])){
			$this->lesson();
		}elseif(isset($route['query']) && $route['query'] == 'new'){
			$this->new();
		}else{
			$this->index();
		}
		
	}
	
	private function index(){
		$route = $this->route;
		$data = new StdData();
		
		$bread = [
			//['pos' => 2, 'link' => 'articles', 'title' => 'Статьи']
		];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Мои курсы']);
		
		// Извлекаем все доступы для данного юзера, не берем те, у которых finished = 0 и истек дедлайн
		$courses = \R::getAll('
			SELECT c.id, p.id AS pid, p.name, p.img, ac.deadline, (SELECT COUNT(*) FROM answer WHERE user_id = :uid AND accepted = 1 OR accepted = 0 AND content IS NULL) AS lessons_passed, (SELECT COUNT(*) FROM lesson WHERE course_id = c.id) AS lessons_total, (SELECT ROUND(lessons_passed / lessons_total * 100)) AS percent, IF(ac.finished = 1 OR (ac.finished IS NULL AND ac.deadline > UNIX_TIMESTAMP(NOW())) OR ac.deadline IS NULL, 1, 0) AS cango, ac.finished, IF(ac.deadline IS NULL, 1, 0) AS nodeadline
				FROM course AS c
				JOIN project AS p ON p.id = c.project_id
				JOIN access AS ac ON ac.course_id = c.id
				WHERE c.id = ANY(SELECT course_id FROM access WHERE user_id = :uid)
				GROUP BY id
		;', ['uid' => $_SESSION['user']['id']]);
        
		$data->setProps(['courses' => $courses]);
		
		$this->set($data);
		
	}
	
	private function new(){
		$route = $this->route;
		$data = new StdData();
		
		$this->view = 'new';
		
		$bread = [
			//['pos' => 2, 'link' => 'articles', 'title' => 'Статьи']
		];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Новые события']);
		
		$eventsList = \R::getAll('
		
		SELECT answer.id AS aid, c.id AS cid, l.id AS lid, l.position AS lpos, l.name AS lname, p.name AS project, FROM_UNIXTIME(answer.date, \'%Y %M %d, %H:%i\') AS adate
		FROM answer
		JOIN lesson AS l ON answer.lesson_id = l.id
		JOIN course AS c ON l.course_id = c.id
		JOIN project AS p ON p.id = c.project_id
		WHERE answer.new = 1 AND answer.user_id = :uid AND curator_id IS NOT NULL
		
		;', ['uid' => $_SESSION['user']['id']]);
		
		$data->setProps(['events_list' => $eventsList]);
		
		$this->set($data);
	}
	
	private function profile(){
		$route = $this->route;
		$data = new StdData();
		$this->view = 'profile';
		
		$bread = [
			//['pos' => 2, 'link' => 'articles', 'title' => 'Статьи']
		];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Ваш профиль']);
		
		$user = \R::load('user', $_SESSION['user']['id']);
		$ava = is_file(WWW . DS . 'upl' . DS . 'users' . DS . $user->id . DS . 'ava.jpg') ? TRUE : FALSE;
		$data->setProps(['user_fields' => array_merge($user->getProperties(), ['ava' => $ava])]);
		
		$deadlines = [];
		foreach($user->ownAccessList as $access){
			$deadlines['cid'] = $access->course_id;
			$deadlines['cname'] = $access->course->project->name;
			//$deadlines['deadline'] = date('c', $access->deadline);
			$deadlines['deadline'] = ($access->deadline != NULL) ? strftime('%Y-%m-%d', $access->deadline) : false;
		}
		$data->setProps(['deadlines' => $deadlines]);
		
		$this->set($data);
	}
	
	public function saveProfileAction(){
		$user = new User;
		//debug($_POST);
		$user->saveProfile($_POST);
	}
	
	private function checkAccess(){
		$route = $this->route;
		// Проверяем есть ли у Юзера досуп к курсу
		$user = \R::load('user', $_SESSION['user']['id']);
		$continue = [];
		foreach($user->ownAccessList as $access){
			if($access->course_id == $route['query']['cid'] &&
            ($access->finished == 1 || (is_null($access->finished) && $access->deadline > time()) || is_null($access->deadline))){
				$continue[] = $access->course_id;
			}
		}
		if(empty($continue)){
			exit ('У вас нет доступа к этому курсу');
		}
	}
	
	private function course(){
		$route = $this->route;
		$this->view = 'course';
		$data = new StdData();
		// /user/area/cid/1
		
		$this->checkAccess();
		
		/*
		$info = \R::getAll('SELECT COUNT(*) AS total, p.name, p.img FROM lesson AS l JOIN course AS c ON c.id = l.course_id JOIN project AS p ON p.id = c.project_id WHERE l.course_id = ?', [$route['query']['cid']]);
		*/
		
		$meta = \R::getRow('
		SELECT c.id, p.id AS pid, p.name, p.img, ac.deadline, (SELECT COUNT(*) FROM answer WHERE user_id = :uid AND accepted = 1 OR accepted = 0 AND content IS NULL) AS lessons_passed, (SELECT COUNT(*) FROM lesson WHERE course_id = c.id) AS lessons_total, (SELECT ROUND(lessons_passed / lessons_total * 100)) AS percent, ac.finished, IF(ac.deadline IS NULL, 1, 0) AS nodeadline
				FROM course AS c
				JOIN project AS p ON p.id = c.project_id
				JOIN access AS ac ON ac.course_id = c.id
				WHERE c.id = :cid
		;', ['cid' => $route['query']['cid'], 'uid' => $_SESSION['user']['id']]);
		
		$data->setProps(['meta' => $meta]);
		
		$lessons = \R::getAll('
		SELECT lesson.position, answer.content, lesson.id, lesson.course_id AS cid, lesson.name, lesson.stop, lesson.bonus, (
			SELECT IF(SUM(answer.accepted) > 0 OR (answer.accepted = 0 AND answer.content IS NULL), \'passed\', \'unpassed\')
		) AS passed
		FROM lesson
		LEFT JOIN answer ON answer.lesson_id = lesson.id
		WHERE lesson.course_id = :cid AND (answer.user_id = :uid OR answer.user_id IS NULL)
		GROUP BY lesson.position
		ORDER BY lesson.position
		;', ['cid' => $route['query']['cid'], 'uid' => $_SESSION['user']['id']]);
		
		for($i = 0; $i < sizeof($lessons); $i++){
			
			if(isset($lessons[$i-1]) && $lessons[$i-1]['passed'] == 'unpassed' && $lessons[$i]['passed'] == 'unpassed'){
				$lessons[$i]['avail'] = FALSE;
			}else{
				$lessons[$i]['avail'] = TRUE;
			}
		}
		
		//debug($lessons);
		
		$bread = [
			['pos' => 2, 'link' => '/user/area', 'title' => 'Мои курсы']
		];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 3, 'title_not_active' => $meta['name']]);
		
		$data->setProps(['lessons' => $lessons]);
		$this->set($data);
	}
	
	private function lesson(){
		$route = $this->route;
		$this->view = 'lesson';
		$data = new StdData();
		// /user/area/cid/1/lid/1
		
		// Проверяем есть ли у Юзера досуп к курсу
		$this->checkAccess();
		
		//Обнуляем значения поля new всех ответов по данному уроку и юзеру
		$news = \R::find('answer', 'new = 1 AND user_id = ? AND curator_id IS NOT NULL', [$_SESSION['user']['id']]);
		
		if(!empty($news)){
			foreach($news as $answer){
				$answer->new = 0;
				\R::store($answer);
			}
			redirect("/user/area/cid/{$route['query']['cid']}/lid/{$route['query']['lid']}");
		}
		
		$lesdata = \R::getRow('
		SELECT l.id, l.name, l.position, l.stop, l.bonus, l.content, p.name AS project, l.course_id, ac.deadline, c.id AS cid, (SELECT IF(COUNT(*), true, false) FROM answer WHERE user_id = :uid AND lesson_id = :lid AND accepted = 1) AS accepted, ac.finished, IF(ac.deadline IS NULL, 1, 0) AS nodeadline
		FROM lesson AS l
		JOIN course AS c ON c.id = l.course_id
		JOIN access AS ac ON ac.course_id = c.id
		JOIN project AS p ON p.id = c.project_id
		WHERE l.id = :lid
		;', ['uid' => $_SESSION['user']['id'], 'lid' => $route['query']['lid']]);
		
		//Чтобы юзер не зашел на урок чужого курса
		if($lesdata['course_id'] != $route['query']['cid']){
			die('У вас нет доступа к данному уроку!');
		}
		
		//Юзер может попытаться зайти в недоступный урок, надо это предотвратить
		$prev_pos = $lesdata['position'] - 1;
		/* Не сделано пока просто не показываем ссылку. Если предыдущий урок не пройден, то на этот тоже не пускаем */
		
		/*
		
		$prev = \R::getRow('
		
		SELECT *, (SELECT IF(COUNT(*), true, false) FROM answer WHERE (user_id = :uid AND lesson_id = :lid) AND accepted = 1 OR (answer.accepted = 0 AND answer.content IS NULL)) AS accepted FROM lesson WHERE position = :pos AND course_id = :cid
		
		;', ['pos' => $prev_pos, 'cid' => $route['query']['cid'], 'uid' => $_SESSION['user']['id'], 'lid' => $route['query']['lid']]);
		
		debug($prev);
		
		*/
		
		//Если не стоп-урок, то создаем ответ для этого урока где content = NULL и accepted = 0
		$nullAnswer = \R::count('answer',
			'content IS NULL AND accepted = 0 AND new = 0 AND lesson_id = ? AND user_id = ? AND curator_id IS NULL'
		, [$route['query']['lid'], $_SESSION['user']['id']]);
		
		// Создаем урок с нулевым контентом при условии, что такого нет и урок не стоп
		if($nullAnswer == 0 && $lesdata['stop'] != 1){
			$nullAnswer = \R::dispense('answer');
			$nullAnswer->date = time();
			$nullAnswer->accepted = 0;
			$nullAnswer->new = 0;
			$nullAnswer->lesson_id = $route['query']['lid'];
			$nullAnswer->user_id = $_SESSION['user']['id'];
			
			\R::store($nullAnswer);
		}
		
		$answers = \R::getAll('
		SELECT answer.id AS aid, content, accepted, date, FROM_UNIXTIME(date, \'%Y-%m-%d, %H:%i\') AS adate, (SELECT IF(curator_id IS NULL, u.id, curator_id)) AS uid, (SELECT IF(curator_id IS NULL, u.name, cur.name)) AS name, (SELECT IF(curator_id IS NULL, u.surname, cur.surname)) AS surname, (SELECT IF(curator_id IS NULL, FALSE, TRUE)) AS bycur
		FROM answer
		JOIN user AS u ON u.id = answer.user_id
		LEFT JOIN user AS cur ON cur.id = answer.curator_id
		WHERE (user_id = :uid) AND lesson_id = :lid AND content IS NOT NULL ORDER BY date
		;', ['uid' => $_SESSION['user']['id'], 'lid' => $route['query']['lid']]);
		
		$ds = DS;
		
		for($i = 0; $i < sizeof($answers); $i++){
		$path = "{$ds}upl{$ds}users{$ds}{$answers[$i]['uid']}{$ds}ava.jpg";
			if(is_file(WWW . $path)){
				$answers[$i]['ava'] = $path;
			}else{
				$answers[$i]['ava'] = "{$ds}img{$ds}interface{$ds}unknown_user.jpg";
			}
		}
		
		$data->setProps(['lesdata' => $lesdata, 'answers' => $answers, 'lid' => $route['query']['lid']]);
		
		$bread = [
			['pos' => 2, 'link' => '/user/area', 'title' => 'Мои курсы'],
			['pos' => 3, 'link' => "/user/area/cid/{$route['query']['cid']}", 'title' => $lesdata['project']]
		];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 4, 'title_not_active' => "Урок {$lesdata['position']}. {$lesdata['name']}"]);
		
		$this->set($data);
	}
	
	private function checkOwner(){
		$route = $this->route;
		// Если uid не существует в запросе или он не равен id текущего пользователя
		if(isset($_SESSION['user']['id'])){
			$query = 'user/area';
		}
	}
	
	private function checkAuth(){
		if(!Service::isAuth()){
			redirect('/user/login');
			/*
			$data = new StdData();
			$this->view = 'login';
			$bread = [];
			$data->setProps([
			'breadcrumbs' => $bread,
			'pos_not_active' => 2,
			'title_not_active' => 'Авторизация',
			]);
			$this->set($data);
			//exit();
			*/
		}
	}
	
	public function agreementAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . " | Пользовательское соглашение",'Пользовательское cоглашение на сайте','пользовательское соглашение');
	}
	
	public function agreeregAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . " | Пользовательское соглашение об использовании личного кабинета",'Пользовательское cоглашение об использовании личного кабинета на сайте','пользовательское соглашение');
	}
	
	public function disclaimerAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . " | Отказ от ответственности",'Отказ от ответственности
за прибыль или доход лиц, приобретающих информационный продукт','отказ от ответственности');
	}
	
	public function cookieAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . " | Политика использования файлов cookie",'Политика использования файлов cookie на сайте','политика использования файлов');
	}
	
	public function privacyAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . " | Политика конфиденциальности",'Политика конфиденциальности в отношении обработки персональных данных','политика конфиденциальности');
	}
	
	public function activationAction(){
		$this->allowQueries(true);
		$route = $this->route;
		
		$cache = Cache::instance();
		$uid = $cache->get($route['query']);
		
		if($uid){
			$user = \R::load('user', $uid);
			$user->activated = 1;
			\R::store($user);
			$cache->delete($route['query']);
			//Вывести сообщение об успешной активации
		}else{
			//Вывести сообщение об ошибке, (возможно прошло 24 часа)
		}
	}
    
}