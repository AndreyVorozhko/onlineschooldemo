<?php

namespace app\controllers\admin;

use vorozhok\base\Controller;
use app\models\admin\AdminModel;
use app\service\Service;
use vorozhok\App;

class AdminController extends Controller
{

	public $layout = 'admin';
	
	public function __construct($route){
		parent::__construct($route);
		
		new AdminModel();
		
		$common = [];
		$common['assets'] = PATH . 'assets/';
		$common['adminlte'] = PATH . 'assets/adminlte/';
		$common['if_admin'] = Service::isAdmin();
		
		$newAnswersCount = \R::count('answer', 'new = 1 AND curator_id IS NULL');
		$newAnswers = \R::getAll('
		
		SELECT answer.id AS aid, answer.user_id AS uid, u.name, u.surname, SUBSTRING(answer.content, 1, 30) as con, answer.date, answer.lesson_id AS lid
		FROM answer
		JOIN user AS u ON u.id = answer.user_id
		WHERE answer.new = 1 AND answer.curator_id IS NULL
		ORDER BY answer.date DESC
		LIMIT 5
		
		;') ;
		
		for($i = 0; $i < count($newAnswers); $i++){
			$newAnswers[$i]['date'] = \time_elapsed_string("@{$newAnswers[$i]['date']}");
			$newAnswers[$i]['con'] = strip_tags($newAnswers[$i]['con']);
		}
		
		//Needed to accept lessons
		$neededToAccept = \R::getAll('
		
		SELECT answer.accepted, answer.lesson_id, answer.user_id, l.name AS lname, u.name AS uname, u.surname AS usurname, SUM(answer.accepted) AS acceptcontrol
		FROM answer
		JOIN lesson AS l ON l.id = answer.lesson_id
		JOIN user AS u ON u.id = answer.user_id
		WHERE answer.curator_id IS NULL AND l.stop = 1
		GROUP BY answer.lesson_id, answer.user_id
		HAVING acceptcontrol = 0
		
		;');
		//debug($neededToAccept);
		
		if(Service::isAdmin()){
			$common['admin-id'] = $_SESSION['user']['id'];
			$common['admin-name'] = $_SESSION['user']['name'];
			$common['admin-surname'] = $_SESSION['user']['surname'];
			$common['all_new_count'] = $newAnswersCount;
			$common['new_answers'] = $newAnswers;
			// Сколько неодобренных уроков
			$common['achtung'] = sizeOf($neededToAccept);
			$common['achtung_info'] = $neededToAccept;
			$ds = DS;
			$user_ava = "{$ds}upl{$ds}users{$ds}{$common['admin-id']}{$ds}ava.jpg";
			$common['user-ava'] = is_file(WWW . $user_ava) ? $user_ava : '/img/interface/unknown_user.jpg';
		}
		
		$common['captcha'] = base64_encode(Service::capGen());
		if(isset($_SESSION['errors'])){
			$common['alert'] = "Alert.fire({icon: 'error',title: '{$_SESSION['errors']}'})";
			unset($_SESSION['errors']);
		}
		if(isset($_SESSION['success'])){
			$common['alert'] = "Alert.fire({icon: 'success',title: '{$_SESSION['success']}'})";
			unset($_SESSION['success']);
		}
		
		if(DEBUG){
			$common['rblogs'] = Service::rbLogs();
		}
		
		App::$reg->setProperty('common',$common);
		
		// redirect all not admin users to admin index page and prevent circular redirection
		if(!Service::isAdmin() && $route['controller'] != 'Main'){
			redirect(PATH . ADMINNAME);
		}

	}
	
}