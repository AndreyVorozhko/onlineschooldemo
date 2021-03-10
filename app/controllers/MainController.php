<?php

namespace app\controllers;

use vorozhok\StdData;
use vorozhok\App;
use app\service\Service;
use Valitron\Validator;

class MainController extends AppController
{

    public function indexAction(){
        redirect(PATH . 'photographer');
    }
	
	public function aboutAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . ' | О проекте', 'Информация о проекте sitelikeyou', 'sitelikeyou');
		$data = new class () extends StdData{
			public function age(){
				$birth = new \DateTime('1985-02-20 18:00');
				$now = new \DateTime('now');
				$diff = $now->diff($birth);
				return $diff->y;
			}
		};
		$bread = [];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'О нас', 'if_about' => true]);
		$this->set($data);
	}
	
	public function certAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . ' | Подарочные сертификаты', 'Подарочные сертификаты для фотографов', 'фотограф, подарок, сертификат');
		$this->view = 'underconstruct';
		$data = new StdData;
		$bread = [];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Подарочные сертификаты', 'if_cert' => true]);
		$this->set($data);
	}
	
	public function portfolioAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . ' | Наше портфолио', 'Портфолио работ веб-студии someproject.ru', 'портфолио');
		$this->view = 'underconstruct';
		$data = new StdData;
		$bread = [];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Наше портфолио', 'if_portfolio' => true]);
		$this->set($data);
	}
	
	public function testimonialsAction(){
		// Идея отзывов следующая - мы пишем кейс в разделе "статьи" о проделанной работе, а клиент пишет свой отзыв в виде комментария
		// на этой же странице мы выводим все комментарии, начиная с самых новых
		$this->setMeta(App::$reg->getProperty('site_name') . ' | Отзывы о нашей работе', 'Отзывы клиентов someproject.ru', 'отзыв');
		$this->view = 'underconstruct';
		$data = new StdData;
		$bread = [];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Отзывы о нашей работе', 'if_testimonials' => true]);
		$this->set($data);
	}
	
	public function feedbackAction(){
		$this->setMeta(App::$reg->getProperty('site_name') . ' | Связаться с нами', 'Обратная связь с командой someproject.ru', 'связаться');
		$data = new StdData;
		$bread = [];
		$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 2, 'title_not_active' => 'Связь с нами', 'if_feedback' => true]);
		$this->set($data);
	}
	
	// feedback form action
	public function fbformAction(){
		$data = (array) json_decode($_POST['json']);
		//echo json_encode(['status' => 'success', 'message' => 'Ваше письмо успешно улетело!']);
		//echo json_encode(['status' => 'fail', 'message' => print_r($data, true)]);
		//exit;
		if(!Service::checkHoney($data)){
			echo json_encode(['status' => 'fail', 'message' => 'Сообщение не отправлено!<br>Код ошибки: there_are_no_honey_in_the_pot']);
		}else{
			if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				// Если к нам идёт Ajax запрос, то ловим его
				//echo json_encode(['status' => 'fail', 'message' => 'Письмо не отправлено, код ошибки 10500']);
				//echo json_encode(['status' => 'success', 'message' => print_r($data, true)]);
				$v = new Validator($data);
				$v->rule('email', 'contact');
				//$eml = 'sitelikeyou@gmail.com';
				$cont = "<p>Контакт, отправленный через форму: {$data['contact']}</p>";
				if($v->validate()){
					//$eml = $data['contact'];
					$cont .= '<p>(Электронная почта)</p>';
				}else{
					$cont .= '<p>Предпочтительные виды связи:</p><ul>';
					foreach($data as $k=>$val){
						switch ($k){
							case ('phonecall'):
								$cont .= ($val == 'on') ? '<li>Телефонный звонок</li>' : '';
								break;
							case ('whatsapp'):
								$cont .= ($val == 'on') ? '<li>WhatsApp</li>' : '';
								break;
							case ('viber'):
								$cont .= ($val == 'on') ? '<li>Viber</li>' : '';
								break;
							case ('telegram'):
								$cont .= ($val == 'on') ? '<li>Telegram</li>' : '';
								break;
						}
					}
					$cont .= '</ul>';
				}
				
				$cont .= "<p>IP отправителя: {$_SERVER['REMOTE_ADDR']}</p>";
				
				$letter = [
				'toname' => 'Администратор someproject.ru',
				'toaddr' => 'mail@someproject.ru',
				'fromname' => 'Форма обратной связи на сайте',
				'fromaddr' => 'mail@someproject.ru',
				'sub' => 'Заказ обратного звонка/сообщения',
				'html' => "<p>{$data['message']}</p>$cont"
				];
				
				$mail = Service::mail($letter);
				if($mail['status'] == 'success'){
					echo json_encode(['status' => 'success', 'message' => '<p>Ваше сообщение успешно отправлено!</p><p>Мы свяжемся с вами в ближайшее время</p>']);
				}else{
					echo json_encode(['status' => 'fail', 'message' => "<p>Ошибка отправки сообщения</p><p>Код ошибки: {$mail['error']}</p>"]);
				}
			}
		}
		//redirect(PATH . 'feedback');
	}
    
}