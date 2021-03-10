<?php

namespace app\controllers\admin;

use vorozhok\StdData;
use app\models\admin\Uscorel;

class AccessController extends AdminController
{
	
	public function indexAction(){
		$this->setMeta('Управление доступом', 'Управление доступом к курсам', 'доступ');
		
		$access_list = \R::getAll("
		
			SELECT a.id, a.date AS unixdate, FROM_UNIXTIME(a.date, '%H:%i, %d-%m-%Y') AS date, proj.name AS course, u.id AS uid, u.name AS uname, u.surname AS usurname, r.name AS rname, r.price AS price, cur.id AS curid, cur.name AS curname, cur.surname AS cursurname, p.id AS pid, FROM_UNIXTIME(p.date, '%H:%i, %d-%m-%Y') AS pdate, p.sum AS psum, v.version
			FROM access AS a
			INNER JOIN project AS proj ON proj.id = a.course_id
			INNER JOIN course AS v ON v.id = a.course_id
			INNER JOIN user AS u ON u.id = a.user_id
			INNER JOIN rate AS r ON r.id = a.rate_id
			INNER JOIN user AS cur ON cur.id = a.curator_id
			INNER JOIN payment AS p ON p.id = a.payment_id
		
		");
		$data = new StdData();
		$bread = [
			//['link' => '/cources', 'title' => 'Управление курсами']
		];
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Управление доступом к курсам', 'access_list' => $access_list]);
		$this->set($data);
	}

}