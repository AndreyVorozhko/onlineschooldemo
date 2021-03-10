<?php

namespace app\controllers\admin;

use vorozhok\StdData;
use app\models\admin\Payments;
use app\service\Service;

class PaymentsController extends AdminController
{
	
	public function indexAction(){
		$this->setMeta('Раздел работы с платежами', 'Просмотр, добавление и редактирование платежей на сайте', 'платеж');
		$payments_list = \R::getAll("
		
			SELECT
				p.id, p.date AS unixdate, FROM_UNIXTIME(p.date, '%H:%i, %d-%m-%Y') AS date, p.sum, p.com, u.id AS uid, u.name, u.surname, proj.name AS course, r.name AS rname, r.price, up.name AS upname, up.price AS uprice, cur.id AS curid, cur.name AS curname, cur.surname AS cursurname
			FROM
				payment AS p
				
			JOIN access AS a ON a.payment_id = p.id
			JOIN user AS u ON u.id = a.user_id
			JOIN project AS proj ON proj.id = a.course_id
			JOIN rate AS r ON r.id = a.rate_id
			LEFT JOIN rate AS up ON up.id = p.upfrom_id
			LEFT JOIN user AS cur ON cur.id = a.curator_id
			
			ORDER BY unixdate DESC
			
		");
        
		$data = new StdData();
		$bread = [
			//['link' => 'cources', 'title' => 'Управление курсами']
		];
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Управление платежами', 'payments_list' => $payments_list]);
		$this->set($data);
	}
	
	public function editAction(){
		$this->setMeta('Редактирование платежа', 'Редактировать платеж вручную', 'платеж');
		$data = new StdData();
		$bread = [
			['link' => 'payments', 'title' => 'Управление платежами']
		];
		$data->setProps(['bread' => $bread, 'title_not_active' => 'Редактирвоание платежа']);
		$this->set($data);
	}
}