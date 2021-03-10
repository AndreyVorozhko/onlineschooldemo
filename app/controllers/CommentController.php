<?php

namespace app\controllers;

use app\models\Comment;
use app\service\Service;

class CommentController extends AppController
{

    public function addAction(){
			$comment = new Comment;
			$art = \R::getRow('SELECT a.alias, acat.alias AS acatalias FROM article AS a INNER JOIN acat ON acat.id = a.acat_id WHERE a.id = ? LIMIT 1', [$_POST['artid']]);
			
		if(!Service::isAuth()){
			$comment->load($_POST);
			$rules = [
				'required' => [
					['yourname'],['commtext'], ['artid']
				],
				'email' => [
					['email']
				],
				'lengthMin' => [
					['commtext', 2], ['yourname', 2]
				],
				'numeric' => [
					['artid']
				]
			];
			$comment->setRules($rules);
		
			if(!$comment->validate() || !$comment->checkHoney()){
				if(!isset($_SESSION['errors'])){
					$_SESSION['errors'] = $comment->getErrors();
				}
			}else{
				unset($comment->attributes['captcha'],$comment->attributes['addr']);
				$comment->save('com');
				$_SESSION['success'] = '<div><p>Ваш комментарий добавлен.</p></div>';
			}
			redirect(PATH . "articles/{$art['acatalias']}/{$art['alias']}#comments");
		}else{
			$rules = [
				'required' => [
					['artid']
				],
				'lengthMin' => [
					['commtext', 2]
				],
				'numeric' => [
					['artid']
				]
			];
			$comment->setRules($rules);
			$data = array_merge($_POST, ['uid' => $_SESSION['user']['id']]);
			$comment->load($data);
			if(!$comment->validate() || !$comment->checkHoney()){
				if(!isset($_SESSION['errors'])){
					$_SESSION['errors'] = $comment->getErrors();
				}
			}else{
				unset($comment->attributes['captcha'],$comment->attributes['addr']);
				$comment->save('com');
				$_SESSION['success'] = "<div><p>Спасибо, {$_SESSION['user']['name']}!<br>Ваш комментарий добавлен.</p></div>";
			}
			
			redirect(PATH . "articles/{$art['acatalias']}/{$art['alias']}#comments");
		}
		
	}
	
	public function deleteAction(){
		if(Service::isAdmin()){
			$this->allowQueries(true);
		
			$route = \vorozhok\Router::getRoute();
			//debug($route['query']);
			$res = \R::hunt('com', 'id=?', [$route['query']]);
			
			$out = ($res == 1) ? ['status' => 'success', 'deleted' => "{$route['query']}"] : ['status' => 'fail'];
			
			echo json_encode($out);
		}
	}
	
	public function editAction(){
		if(Service::isAdmin()){
			$this->allowQueries(true);
			$route = \vorozhok\Router::getRoute();
			$data = json_decode($_POST['json']);
			$newcom = $data->newcom;
			$res = \R::exec('UPDATE com SET commtext=? WHERE id=?', [$newcom, $route['query']]);
			$out = ($res == 1) ? ['status' => 'success', 'updated' => "{$route['query']}"] : ['status' => 'fail'];
			
			echo json_encode($out);
		}
	}
    
}