<?php

namespace vorozhok\libs;

class Pagination{

    public $currentPage;
    public $perpage;
    public $total;
    public $countPages;
    public $uri;

    public function __construct($page, $perpage, $total){
        $this->perpage = $perpage;
        $this->total = $total;
        $this->countPages = $this->getCountPages();
        $this->currentPage = $this->getCurrentPage($page);
        $this->uri = $this->getParams();
    }

    public function getHtml(){
        $back = null; // ссылка НАЗАД
        $forward = null; // ссылка ВПЕРЕД
        $startpage = null; // ссылка В НАЧАЛО
        $endpage = null; // ссылка В КОНЕЦ
        $page2left = null; // вторая страница слева
        $page1left = null; // первая страница слева
        $page2right = null; // вторая страница справа
        $page1right = null; // первая страница справа

        if( $this->currentPage > 1 ){
			if($this->currentPage == 2){
				$back = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}\">&lt;</a></li>";
			}else{
				$back = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}page/" .($this->currentPage - 1). '">&lt;</a></li>';
			}
        }
        if( $this->currentPage < $this->countPages ){
            $forward = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}page/" .($this->currentPage + 1). '">&gt;</a></li>';
        }
        if( $this->currentPage > 3 ){
            $startpage = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}\">&laquo;</a></li>";
        }
        if( $this->currentPage < ($this->countPages - 2) ){
            $endpage = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}page/{$this->countPages}\">&raquo;</a></li>";
        }
        if( $this->currentPage - 2 > 0 ){
			if($this->currentPage == 3){
				$page2left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}\">" .($this->currentPage - 2). '</a></li>';
			}else{
				$page2left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}page/" .($this->currentPage-2). '">' .($this->currentPage - 2). '</a></li>';
			}
        }
        if( $this->currentPage - 1 > 0 ){
			if($this->currentPage == 2){
				$page1left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}\">" .($this->currentPage-1). '</a></li>';
			}else{
				$page1left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}page/" .($this->currentPage-1). '">' .($this->currentPage-1). '</a></li>';
			}
        }
        if( $this->currentPage + 1 <= $this->countPages ){
            $page1right = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}page/" .($this->currentPage + 1). '">' .($this->currentPage+1). '</a></li>';
        }
        if( $this->currentPage + 2 <= $this->countPages ){
            $page2right = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}page/" .($this->currentPage + 2). '">' .($this->currentPage + 2). '</a></li>';
        }
		
		$out = '';
		
		if($this->countPages > 1){
		$out = "<ul itemscope itemtype=\"http://schema.org/SiteNavigationElement/Pagination\" class=\"pagination\">$startpage$back$page2left$page1left<li class=\"active\"><a itemprop=\"url\">{$this->currentPage}</a></li>$page1right$page2right$forward$endpage</ul>";
		}

        return $out;
    }

    public function __toString(){
        return $this->getHtml();
    }

    public function getCountPages(){
        return ceil($this->total / $this->perpage) ?: 1;
    }

    public function getCurrentPage($page){
        if(!$page || $page < 1) $page = 1;
        if($page > $this->countPages) $page = $this->countPages;
        return $page;
    }

    public function getStart(){
        return ($this->currentPage - 1) * $this->perpage;
    }

    public function getParams(){
        $route = \vorozhok\Router::getRoute();
		$controller = strtolower($route['controller']);
		$uri = "$controller/{$route['action']}/";
		if(isset($route['query'])){
			foreach($route['query'] as $k=>$v){
				if($k != 'page'){
					$uri .= "$k/$v/";
				}
			}
		}
        return $uri;
    }

}