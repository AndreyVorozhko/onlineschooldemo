<?php

namespace app\controllers;

use vorozhok\App;
use vorozhok\Cache;
use vorozhok\StdData;
use vorozhok\libs\ArticlePagination;
use vorozhok\libs\CommentPagination;
use app\service\Service;

class ArticlesController extends AppController
{

    public function viewAction(){
		
		$this->allowQueries(true);
		
		$data = new StdData();
		
		$route = \vorozhok\Router::getRoute();
		$now = time();
		if(!isset($route['category']) || $route['category'] == 'page'){
			$this->setMeta('Все статьи | ' . App::$reg->getProperty('site_name'),'Полезные статьи о продвижении фотографа в фотобизнесе и другие приемы маркетинга для фотографов','продвижение для фотографов');
			$bread = [
				//['pos' => 2, 'link' => 'articles', 'title' => 'Все статьи']
			];
			$page = isset($route['query']) ? $route['query'] : 1;
			
			if($page != 1){
				$canonical = '<link rel="canonical" href="' . PATH . 'articles"/>';
				$data->setProp('canonical', $canonical);
			}elseif(isset($route['query']) && $route['query'] == 1){
				redirect(PATH . 'articles');
			}
			
			$perpage = App::$reg->getProperty('pagination');
			$total = \R::count('article', "");
			
			$pagination = new ArticlePagination($page, $perpage, $total);
			$start = $pagination->getStart();
			
			$arts = \R::getAll("SELECT a.id, a.title, a.alias, LEFT(html, 1600) html, pubdate AS unixdate, FROM_UNIXTIME(pubdate, '%H:%i, %d-%m-%Y') AS pubdate, FROM_UNIXTIME(pubdate, '%Y-%m-%d') AS metadate, a.aimg, IFNULL((SELECT COUNT(com.id) FROM com WHERE com.article_id=a.id),0) AS comments, acat.title AS cat, acat.alias AS cat_alias FROM article AS a INNER JOIN acat ON acat.id = a.acat_id WHERE a.pubdate < ? ORDER BY unixdate DESC LIMIT $start, $perpage", [$now]);
			
			$data->setProps([
			'breadcrumbs' => $bread,
			'pos_not_active' => 2,
			'title_not_active' => 'Все статьи',
			'cat-title' => 'Все статьи',
			'cycle-link' => true,
			'if_articles' => true
			]);
			
			$data->setProp('art_list', $arts);
			$data->setProp('pagination', $pagination);
			
		}elseif(!isset($route['query']) || (isset($route['query']['page']) && sizeof($route['query']) < 2)){
			// sizeof($route['query']) < 2 needed for prevent content dublicating (SEO purposes)
			$cats = App::$reg->getProperty('cats');
			$cat = false;
			foreach($cats as $arr){
				if($arr['alias'] == $route['category']){
					$cat = $arr;
				}
			}
			if(!$cat){
			throw new \Exception("There are no category '{$route['category']}' here!", 404);
			}
			
			$this->setMeta("{$cat['title']} | " . App::$reg->getProperty('site_name'),"Полезные статьи нашего блога на тему {$cat['title']}", $route['category']);
			
			$page = isset($route['query']['page']) ? $route['query']['page'] : 1;
			
			if($page != 1){
				$canonical = '<link rel="canonical" href="' . PATH . "articles/{$route['category']}\"/>";
				$data->setProp('canonical', $canonical);
			}elseif(isset($route['query']['page']) && $route['query']['page'] == 1){
				redirect(PATH . "articles/{$route['category']}");
			}
			
			$perpage = App::$reg->getProperty('pagination');
			$total = \R::count('article', "acat_id={$cat['id']}");
			
			$pagination = new ArticlePagination($page, $perpage, $total);
			$start = $pagination->getStart();
			
			$arts = \R::getAll("SELECT a.id, a.title, a.alias, LEFT(html, 1600) html, pubdate AS unixdate, FROM_UNIXTIME(pubdate, '%H:%i, %d-%m-%Y') AS pubdate, FROM_UNIXTIME(pubdate, '%Y-%m-%d') AS metadate, a.aimg, IFNULL((SELECT COUNT(com.id) FROM com WHERE com.article_id=a.id),0) AS comments, acat.title AS cat, acat.alias AS cat_alias FROM article AS a INNER JOIN acat ON acat.id = a.acat_id WHERE a.pubdate < ? AND acat_id = ? ORDER BY unixdate DESC LIMIT $start, $perpage", [$now, $cat['id']]);
			
			$bread = [
				['pos' => 2, 'link' => 'articles', 'title' => 'Статьи']
			];
			$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 3, 'title_not_active' => $cat['title'], 'cat-title' => $cat['title'], 'cycle-link' => false]);
			$data->setProp('art_list', $arts);
			$data->setProp('pagination', $pagination);
		}else{
			$this->view = 'article';
			
			$cats = App::$reg->getProperty('cats');
			$cat_route_id = 0;
			foreach($cats as $cat){
				if($cat['alias'] == $route['category']){
					$cat_route_id = $cat['id'];
					$cat_route_title = $cat['title'];
				}
			}
			
			// This check prevents content dublicating (SEO purposes) before database query (optimization)
			if(is_array($route['query'])){
				throw new \Exception('There is no such article here!', 404);
			}
			
			$art = \R::getRow("SELECT a.id AS aid, a.title, a.seotitle, a.seodesc, a.seokeyw, a.alias, a.html, FROM_UNIXTIME(pubdate, '%H:%i, %d-%m-%Y') AS pubdate, FROM_UNIXTIME(pubdate, '%Y-%m-%d') AS metadate, a.aimg, FROM_UNIXTIME(lastmod, '%Y-%m-%d') AS lastmod, a.acat_id, IFNULL((SELECT COUNT(com.id) FROM com WHERE com.article_id=aid),0) AS comments, acat.title AS cat, acat.alias AS cat_alias FROM article AS a INNER JOIN acat ON acat.id = a.acat_id WHERE a.alias = ? AND a.acat_id = ? LIMIT 1", [$route['query'], $cat_route_id]);
			
			if(empty($art)){
				throw new \Exception('There is no such article here!', 404);
			}
			
			$this->setMeta("{$art['seotitle']} | " . App::$reg->getProperty('site_name'),$art['seodesc'], $art['seokeyw']);
			
			$bread = [
				['pos' => 2, 'link' => 'articles', 'title' => 'Статьи'],
				['pos' => 3, 'link' => "articles/{$route['category']}", 'title' => $cat_route_title]
			];
			$data->setProps(['breadcrumbs' => $bread, 'pos_not_active' => 4, 'title_not_active' => $art['title']]);
			
			$data->setProps($art);
			
			$page = isset($_GET['page']) ? $_GET['page'] : 1;
			
			if($page != 1){
				$canonical = '<link rel="canonical" href="' . PATH . "articles/{$route['category']}/{$art['alias']}\"/>";
				$data->setProp('canonical', $canonical);
			}elseif(isset($_GET['page']) && $_GET['page'] == 1){
				redirect(PATH . "articles/{$route['category']}/{$art['alias']}#comments");
			}
			
			$perpage = App::$reg->getProperty('pagination');
			$total = \R::count('com', "article_id={$art['aid']}");
			
			$pagination = new CommentPagination($page, $perpage, $total);
			$start = $pagination->getStart();
			$data->setProp('pagination', $pagination);
			
			$coms = \R::getAll("SELECT com.id AS comid, com.user_id,  com.commtext, IFNULL(com.yourname, (SELECT name FROM user WHERE id = user_id)) AS yourname, com.comdate AS unixdate, FROM_UNIXTIME(com.comdate, '%H:%i, %d-%m-%Y') AS comdate, (SELECT IF(r.name = :role, true, false)) AS admin
			FROM com
			LEFT JOIN role AS r ON com.user_id = r.id
			WHERE com.article_id = :aid ORDER BY unixdate LIMIT $start, $perpage", ['role' => 'admin', 'aid' => $art['aid']]);
			
			$user = $_SESSION['user'] ?? [];
			//debug($_SESSION);
			$data->setProps($user);
			
			$data->setProp('coms', $coms);
			//debug($coms);
		}
		
		$this->set($data);
    }
    
}