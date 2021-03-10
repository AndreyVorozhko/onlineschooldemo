<?php

namespace app\controllers\admin;

use vorozhok\StdData;
use app\models\admin\Course;
use app\service\Service;

class CoursesController extends AdminController
{
	
	public function indexAction(){
		$this->setMeta('Раздел работы с курсами', 'Просмотр, добавление и редактирование курсов на сайте', 'курсы');
		$courses = \R::getAll('SELECT c.id, c.version, FROM_UNIXTIME(c.creation, \'%d %M %Y, %H:%i\') AS creation, status.name AS status, project.name AS project FROM course AS c INNER JOIN status ON status.id = c.status_id INNER JOIN project ON project.id = c.project_id');
		$data = new StdData();
		
		foreach($courses as $k=>$course){
			$class = ($course['status'] == 'development') ? 'text-warning': (
			($course['status'] == 'production') ? 'text-success': (
			($course['status'] == 'archieved') ? 'text-danger': false));
			
			$courses[$k]['status'] = "<span class=\"$class\">{$course['status']}</span>";
		}
		
		$bread = [
			//['link' => '/cources', 'title' => 'Управление курсами']
		];
		$data->setProp('course_list', $courses);
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Управление курсами']);
		$this->set($data);
	}
	
	public function projectAction(){
		$this->setMeta('Проекты курсов', 'Список всех проектов на сайте', 'проект');
		
		$heads = \R::getAll('SELECT id, img, name FROM project');
		
		$data = new StdData();
		$bread = [
			//['link' => '/cources', 'title' => 'Управление курсами']
		];
		$data->setProp('project_list', $heads);
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Управление проектами']);
		$this->set($data);
	}
	
	public function addProjectAction(){
		$this->setMeta('Создать новый курс', 'Форма создания нового проекта курса', 'проект');
		
		$data = new StdData();
		$bread = [
			['link' => 'courses/project', 'title' => 'Управление проектами']
		];
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Создать новый проект']);
		$this->set($data);
		if(isset($_POST['submit'])){
			$data = $_POST;
			$project = new Course();
			$project->load($data);
			
			try{
				$project->saveProject('project');
				$_SESSION['success'] = "Проект \"{$data['name']}\" успешно добавлен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось добавить проект \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect(ADMIN . '/courses/project');
			}
		}
	}
	
	public function editProjectAction(){
		$this->setMeta('Редактировать имя проекта', 'Изменить имя проекта курса', 'проект');
		$this->allowQueries(true);
		$route = $this->route;
		$project = \R::load('project', $route['query']);
		
		$data = new StdData();
		$bread = [
			['link' => 'courses/project', 'title' => 'Управление проектами']
		];
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Редактировать проект', 'id' => $project->id, 'name' => $project->name]);
		$this->set($data);
		
		if(isset($_POST['submit'])){
			$data = $_POST;
			$data['pid'] = $route['query'];
			$project = new Course();
			$project->load($data);
			
			try{
				$project->saveProject('project');
				$_SESSION['success'] = "Проект \"{$data['name']}\" успешно изменен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось изменить проект \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect(ADMIN . '/courses/project');
			}
		}
	}
	
	public function addAction(){
		if(!isset($_POST['submit'])){
			$this->setMeta('Добавить новую версию курса', 'Форма добавления новой версии курса', 'версия');
			$data = new StdData();
			$bread = [
				['link' => 'courses', 'title' => 'Управление версиями курсов']
			];
			
			$projects = \R::getAll('SELECT id AS pid, name AS pname FROM project');
			$statuses = \R::getAll("SELECT id AS sid, CONCAT(name, ' (', rus, ') ') AS sname FROM status");
			
			$data->setProps(['bread' => $bread, 'title_not_active' => 'Новая версия курса', 'project_list' => $projects, 'status_list' => $statuses]);
			$this->set($data);
		}else{
			$data = $_POST;
			$course = new Course();
			$data['status'] = \R::load('status', $data['status']);
			$data['project'] = \R::load('project', $data['project']);
			$data['creation'] = time();
			$course->load($data);
			
			try{
				$course->save('course');
				$_SESSION['success'] = "Версия {$data['version']} курса \"{$data['project']->name}\" успешно добавлена!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось добавить версию {$data['version']} курса \"{$data['project']->name}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect(ADMIN . '/courses');
			}
		}
	}
	
	public function editAction(){
		
		$route = $this->route;
		$course = \R::load('course', $route['query']);
		$course_name = $course->project->name;
		
		if(!isset($_POST['submit'])){
			$this->setMeta('Изменить курс', 'Форма изменения курса', 'редактировать');
			$this->allowQueries(true);
			$data = new StdData();
			$bread = [
				['link' => '/' . ADMINNAME . '/courses', 'title' => 'Управление курсами']
			];
			
			$projects = \R::getAll('SELECT id AS pid, name AS pname FROM project');
			$project_list = [];
			
			foreach($projects as $projest){
				if($projest['pid'] == $course->project->id){
					$if_project = true;
				}else{
					$if_project = false;
				}
					$project_list[] = ['pid' => $projest['pid'], 'pname' => $projest['pname'], 'if_project' => $if_project];
			}
			
			$statuses = \R::getAll("SELECT id AS sid, CONCAT(name, ' (', rus, ') ') AS sname FROM status");
			$status_list = [];
			
			foreach($statuses as $status){
				if($status['sid'] == $course->status->id){
					$if_status = true;
				}else{
					$if_status = false;
				}
				$status_list[] = ['sid' => $status['sid'], 'sname' => $status['sname'], 'if_status' => $if_status];
			}
			
			$data->setProps([
				'bread' => $bread,
				'title_not_active' => 'Редактировать курс',
				'project_list' => $project_list,
				'status_list' => $status_list,
				"version" => $course->version,
				"notes" => $course->notes,
				"changes" => $course->changes
				]);
			$this->set($data);
		}else{
			$data = $_POST;
			$data['cid'] = $route['query'];
			$data['status'] = \R::load('status', $data['status']);
			//$data['project'] = \R::load('project', $data['project']); // because disabled in the form
			$course = new Course();
			$course->load($data);
			try{
				$course->save('course');
				$_SESSION['success'] = "Курс \"{$course_name}\" успешно изменен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось отредактировать курс \"{$course_name}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect(ADMIN . '/courses');
			}
		}
	}
	
	public function addrateAction(){
			$route = $this->route;
		if(!isset($_POST['submit'])){
			$this->setMeta('Добавление нового тарифа для курса', 'Новый тариф для курса', 'тариф');
			
			$course = \R::load('course', $route['query']['cid']);
			
			$this->allowQueries(true);
			$data = new StdData();
			$bread = [
				['link' => '/' . ADMINNAME . '/courses', 'title' => 'Управление курсами']
			];
			$data->setProps(['bread' => $bread, 'title_not_active' => 'Новый тариф', "cname" => $course->name]);
			$this->set($data);
		}else{
			$data = $_POST;
			$data['cid'] = $route['query']['cid'];
			$rate = new Course();
			$rate->load($data);
			
			try{
				$rate->saveRate();
				$_SESSION['success'] = "Тариф \"{$data['name']}\" успешно добавлен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось добавить тариф \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect(ADMIN . "/courses/view/{$route['query']['cid']}");
			}
		}
	}
	
	public function addlessonAction(){
		
		$route = $this->route;
		
		if(!isset($_POST['submit'])){
			$this->setMeta('Добавление нового урока', 'Новый урок курса', 'урок');
			
			$course = \R::load('course', $route['query']['cid']);
			$rate_list = [];
			foreach($course->ownRateList as $rate){
				$rate_list[] = ['rid' => $rate->id, 'rname' => $rate->name];
			}
			
			$this->allowQueries(true);
			$data = new StdData();
			$bread = [
				['link' => '/' . ADMINNAME . '/courses', 'title' => 'Управление курсами'],
				['link' => '/' . ADMINNAME . "/courses/view/cid/{$route['query']['cid']}", 'title' => "Просмотр курса"],
			];
			
			$nextpos = \R::getCell('SELECT MAX(position) FROM lesson LIMIT 1');
			$nextpos += 1;
			
			$lesson_list = \R::getAll('SELECT id, name, position AS pos FROM lesson WHERE course_id=? ORDER BY position', [$course->id]);
			
			$data->setProps(['bread' => $bread, 'title_not_active' => "Новый урок для курса", "cname" => $course->name, 'rate_list' => $rate_list, 'nextpos' => $nextpos, 'lesson_list' => $lesson_list]);
			$this->set($data);
			//debug($route['query']);
		}else{
			$data = $_POST;
			$data['cid'] = $route['query']['cid'];
			$lesson = new Course();
			$lesson->load($data);
			
			try{
				$lesson->saveLesson();
				$_SESSION['success'] = "Урок \"{$data['name']}\" успешно добавлен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось добавить урок \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				redirect(ADMIN . "/courses/view/{$route['query']['cid']}");
			}
		}
	}
	
	public function viewAction(){
		$this->setMeta('Просмотр курса', 'Просмотр курса, добавление и редактирование тарифов, уроков...', 'курс');
		$this->allowQueries(true);
		$route = $this->route;
		
		$data = new StdData();
		$bread = [
			['link' => 'courses', 'title' => 'Управление курсами']
		];
		
		$course = \R::load('course', $route['query']);
		
		$lesson_list = [];
		foreach($course->ownLessonList as $lesson){
			
			$rates = '';
			if($lesson->allrates === NULL){
				foreach($lesson->sharedRateList as $rate){
						$rates .= "$rate->name, ";
				}
			}else{
				$rates = 'все';
			}
			
			$rates = rtrim($rates, ', ');
			
			$lesson_list[] = [
			'lid' => $lesson->id,
			'position' => $lesson->position,
			'name' => $lesson->name,
			'stop' => $lesson->stop,
			'rates' => $rates,
			'bonus' => $lesson->bonus
			];
		}
		
		
		$rate_list = [];
		foreach($course->ownRateList as $rate){
			$promodate = $rate->promodate;
			$humandate = date("F j, Y, g:i a", $promodate);
			$status = ($promodate < time()) ? "<span class=\"text-danger\">(акция завершена)</span>" : "<span class=\"text-success\">(акция действует)</span>";
			$promodate = "$humandate $status";
            $avail = $rate->avail == 1 ? "<span class=\"text-success\">Да</span>" : "<span class=\"text-danger\">Нет</span>";
			$rate_list[] = ['rid' => $rate->id, 'rname' => $rate->name, 'price' => $rate->price, 'promodate' => $promodate, 'promoprice' => $rate->promoprice, 'lim' => $rate->lim, 'avail' => $avail];
		}
		
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Просмотр курса', 'id' => $route['query'], 'cname' => $course->project->name, 'lesson_list' => $lesson_list, 'rate_list' => $rate_list]);
		//debug($course);
		
		$this->set($data);
	}
	
	public function lessonEditAction(){
		$this->allowQueries(true);
		$route = $this->route;
		if(!isset($_POST['submit'])){
			$this->setMeta('Редактирование урока', 'Отредактировать урок', 'урок');
			
			$lesson = \R::load('lesson', $route['query']);
			
			$data = new StdData();
			$bread = [
				['link' => 'courses', 'title' => 'Управление курсами'],
				['link' => "courses/view/{$lesson->course->id}", 'title' => "Просмотр курса"],
			];
			
			$if_stop = $lesson->stop ?? false;
			$if_bonus = $lesson->bonus ?? false;
			
			$rate_list = [];
			$all_sel = ($lesson->allrates == 1) ? true : false;
			$rate_list[] = ['rid' => 'all', 'rname' => 'Все', 'if_sel' => $all_sel];
			foreach($lesson->course->ownRateList as $rate){
				$if_sel = false;
				$search_flag = false;
				
				//search for $rate->id in shatedRateList
				foreach($lesson->sharedRateList as $shared){
					if($shared->id == $rate->id){
						$search_flag = true;
						break;
					}
				}
				
				if($lesson->allrates === NULL && $search_flag === true){
					$if_sel = true;
				}
				$rate_list[] = ['rid' => $rate->id, 'rname' => $rate->name, 'if_sel' => $if_sel];
			}
			
			$lesson_list = \R::getAll('SELECT id, name, position AS pos FROM lesson WHERE course_id=? AND id!=? ORDER BY position', [$lesson->course->id, $lesson->id]);
			
			$data->setProps([
				'bread' => $bread,
				'title_not_active' => 'Редактировать урок',
				
				'position' => $lesson->position,
				'lesson_list' => $lesson_list,
				'name' => $lesson->name,
				'if_stop' => $if_stop,
				'if_bonus' => $if_bonus,
				'content' => $lesson->content,
				'rate_list' => $rate_list,
				'cname' => $lesson->course->project->name
			]);
			
			$this->set($data);
		}else{
			$data = $_POST;
			$data['lid'] = $route['query'];
			$lesson = new Course();
			$lesson->load($data);
			
			try{
				$lesson->editLesson();
				$_SESSION['success'] = "Урок \"{$data['name']}\" успешно изменен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось изменить урок \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				$course_id = \R::getCell('SELECT course_id FROM lesson WHERE id = ? LIMIT 1', [$route['query']]);
				redirect(ADMIN . "/courses/view/$course_id");
			}
		}
	}
	
	public function rateEditAction(){
		$this->allowQueries(true);
		$route = $this->route;
		if(!isset($_POST['submit'])){
			$this->setMeta('Редактирование тарифа', 'Отредактировать тариф', 'тариф');
			
			$rate = \R::load('rate', $route['query']);
			$data = new StdData();
			$bread = [
				['link' => 'courses', 'title' => 'Управление курсами'],
				['link' => "courses/view/cid/{$rate->course->id}", 'title' => "Просмотр курса"],
			];
			
			$promodate = date("Y, m-1, j, h, i, s", $rate->promodate);
			
			$data->setProps([
				'bread' => $bread,
				'title_not_active' => 'Редактировать тариф',
				
				'name' => $rate->name,
				'lim' => $rate->lim,
				'avail' => $rate->avail,
				'cname' => $rate->course->name,
				'price' => $rate->price,
				'promodate' => $promodate,
				'promoprice' => $rate->promoprice,
			]);
            

			$this->set($data);
		}else{
			$data = $_POST;
			$data['rid'] = $route['query'];
			$rate = new Course();
			$rate->load($data);
			
			try{
				$rate->editRate();
				$_SESSION['success'] = "Тариф \"{$data['name']}\" успешно изменен!";
			}catch(\Exception $e){
				$_SESSION['errors'] = "Не удалось изменить тариф \"{$data['name']}\"<br>Ошибка: {$e->getMessage()}";
			}finally{
				$course_id = \R::getCell('SELECT course_id FROM rate WHERE id = ? LIMIT 1', [$route['query']]);
				redirect(ADMIN . "/courses/view/$course_id");
			}
		}
	}
    
	// Список новых ответов к урокам
	public function newAnswersAction(){	
		$data = new StdData();
			$bread = [
				['link' => 'courses', 'title' => 'Управление курсами']
			];
			
		$newAnswers = \R::getAll('
		
		SELECT answer.id AS aid, answer.user_id AS uid, u.name, u.surname, SUBSTRING(answer.content, 1, 30) as con, answer.date, l.id AS lid, l.name AS lname, p.name AS pname
		FROM answer
		JOIN user AS u ON u.id = answer.user_id
		JOIN lesson AS l ON l.id = answer.lesson_id
		JOIN course AS c ON c.id = l.course_id
		JOIN project AS p ON p.id = c.project_id
		WHERE answer.new = 1 AND answer.curator_id IS NULL
		
		;');
		
		for($i = 0; $i < count($newAnswers); $i++){
			$newAnswers[$i]['date'] = \time_elapsed_string("@{$newAnswers[$i]['date']}");
			$newAnswers[$i]['con'] = strip_tags($newAnswers[$i]['con']);
		}
		
		$data->setProps([
		'bread' => $bread,
		'title_not_active' => 'Новые ответы к урокам',
		'answers_list' => $newAnswers
		]);
		$this->set($data);
	}
	
	// Просмотр нового урока
	public function newAnswerViewAction(){
		$this->allowQueries(true);
		$route = $this->route;
		
		$data = new StdData();
			$bread = [
				['link' => 'courses', 'title' => 'Управление курсами']
			];
			
		/*
		$lesson = \R::getRow('
		SELECT lesson.name, lesson.position, lesson.content, p.name AS pname, u.id AS uid, u.name AS uname, u.surname AS usurname
		FROM lesson
		JOIN course AS c ON c.id = lesson.course_id
		JOIN project AS p ON p.id = c.project_id
		JOIN user AS u ON u.id = :uid
		WHERE lesson.id = :lid
		
		;', ['lid' => $route['query']['lid'], 'uid' => $route['query']['uid']]);
		*/
		
		$lessonBean = \R::load('lesson', $route['query']['lid']);
		$answers = [];
		
		foreach($lessonBean->ownAnswerList as $answer){
			
			$curator = $answer->fetchAs('user')->curator;
			$user = $answer->user;
			
			$answers[] = [
			'id' => $answer->id,
			'unixdate' => $answer->date,
			'date' => \time_elapsed_string("@{$answer->date}"),
			'content' => $answer->content,
			'accepted' => $answer->accepted,
			'new' => $answer->new,
			'uid' => $curator->id ? $curator->id : $user->id,
			'uname' => $curator->name ? $curator->name : $user->name,
			'admin' => $curator->name ? true : false,
			'usurname' => $curator->surname ? $curator->surname : $user->surname
			];
		}
		
		// Сортировка многомерного массива по значению поля
		function array_multisort_value()
		{
			$args = func_get_args();
			$data = array_shift($args);
			foreach ($args as $n => $field) {
				if (is_string($field)) {
					$tmp = array();
					foreach ($data as $key => $row) {
						$tmp[$key] = $row[$field];
					}
					$args[$n] = $tmp;
				}
			}
			$args[] = &$data;
			call_user_func_array('array_multisort', $args);
			return array_pop($args);
		}
		
		$answers = array_multisort_value($answers, 'unixdate', SORT_ASC);
		
		$lesson = [
			'position' => $lessonBean->position,
			'name' => $lessonBean->name,
			'project' => $lessonBean->course->project->name,
			'stop' => $lessonBean->stop,
			'bonus' => $lessonBean->bonus,
			'content' => $lessonBean->content,
			'answers' => $answers
		];
		
		//debug($lesson);
		
		
		// При просмотре все поля по данному юзеру и уроку answer.new становятся 0
		\R::exec('
		
		UPDATE answer SET new = 0 WHERE lesson_id = ? AND user_id = ? AND curator_id IS NULL
		
		;', [$route['query']['lid'], $route['query']['uid']]);
		
			
		$data->setProps([
		'bread' => $bread,
		'title_not_active' => 'Просмотр ответов',
		'lesson' => $lesson,
		'lid' => $route['query']['lid'],
		'uid' => $route['query']['uid']
		]);
		$this->set($data);
	}
	
	public function acceptAnswerAction(){
		$route = $this->route;
		$this->allowQueries(TRUE);
		
		if(Service::isAdmin()){
			$answer = \R::load('answer', $route['query']);
			$answer->accepted = 1;
            
            $lessonId = $answer->lesson->id;
            $courseId = $answer->lesson->course->id;
            $userId = $answer->user->id;
            
            $lastStopLessonId = \R::getCell('SELECT id FROM lesson WHERE stop = 1 AND course_id = ? ORDER BY position DESC LIMIT 1', [$courseId]);
            
            if($lessonId == $lastStopLessonId){  // если у нас последний стоп-урок курса
                $access = \R::findOne('access', 'user_id = ? AND course_id = ?', [$userId, $courseId]); // находим нужный доступ
                $access->finished = 1;
                \R::store($access);
            }

			if(\R::store($answer)){
				$_SESSION['success'] = "Ответ id={$answer->id} пользователя {$answer->user->name} {$answer->user->surname} успешно принят!";
			}else{
				$_SESSION['error'] = "Ошибка! Не удалось принять ответ id={$answer->id} пользователя {$answer->user->name} {$answer->user->surname}!";
			}
		}
		redirect();
	}
	
	public function deleteAnswerAction(){
		$route = $this->route;
		$this->allowQueries(TRUE);
		
		if(Service::isAdmin()){
			if(\R::hunt('answer', 'id = ?', [$route['query']])){
				$_SESSION['success'] = "Ответ id={$route['query']} успешно удален!";
			}else{
				$_SESSION['error'] = "Ошибка! Не удалось удалить ответ id={$route['query']}!";
			}
		}
		redirect();
	}
	
	public function addAnswerAction(){
		
		$answer = \R::dispense('answer');
		$answer->user = \R::load('user', $_POST['uid']);
		$answer->lesson = \R::load('lesson', $_POST['lid']);
		$answer->new = 1;
		$answer->curator = \R::load('user', $_SESSION['user']['id']);
		$answer->content = $_POST['answer'];
		$answer->date = time();
		$store = \R::store($answer);
		if($store){
			$_SESSION['success'] = "Ваш ответ id={$store} успешно добавлен!";
		}else{
			$_SESSION['error'] = "Не удалось добавить ответ!";
		}
		redirect();
	}
	
	public function lessonViewAction(){
		$route = $this->route;
		$this->allowQueries(true);
		
		$this->layout = 'userarea';
		
		$lesdata = \R::getRow('
		SELECT l.id, l.name, l.position, l.stop, l.bonus, l.content, p.name AS project, l.course_id, c.id AS cid
		FROM lesson AS l
		JOIN course AS c ON c.id = l.course_id
		JOIN project AS p ON p.id = c.project_id
		WHERE l.id = ?
		;', [$route['query']]);
		
		$data = new StdData();
		
		$bread = [
			['link' => 'courses', 'title' => 'Управление курсами']
		];
		
		$data->setProps([
		'bread' => $bread,
		'title_not_active' => 'Просмотр урока в интерфейсе пользователя',
		'lesdata' => $lesdata
		]);
		$this->set($data);
		
	}
	
	public function lessonDeleteAction(){
		$this->allowQueries(true);
		$route = $this->route;
		$lesson = \R::load('lesson', $route['query']);
		try{
			\R::trash($lesson);
			$_SESSION['success'] = "Урок \"{$lesson->name}\" удален!";
		}catch(\Exception $e){
			$_SESSION['errors'] = "Не удалось удалить урок \"{$lesson->name}\"<br>Ошибка: {$e->getMessage()}";
		}finally{
			redirect();
		}
	}
	
	public function rateDeleteAction(){
		$this->allowQueries(true);
		$route = $this->route;
		$rate = \R::load('rate', $route['query']);
		try{
			\R::trash($rate);
			$_SESSION['success'] = "Тариф \"{$rate->name}\" удален!";
		}catch(\Exception $e){
			$_SESSION['errors'] = "Не удалось удалить тариф \"{$rate->name}\"<br>Ошибка: {$e->getMessage()}";
		}finally{
			redirect();
		}
	}
}