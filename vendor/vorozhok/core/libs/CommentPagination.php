<?php

namespace vorozhok\libs;

class CommentPagination extends Pagination{
	
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
				$back = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}#comments\">&lt;</a></li>";
			}else{
				$back = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}?page=" .($this->currentPage - 1). '#comments">&lt;</a></li>';
			}
        }
        if( $this->currentPage < $this->countPages ){
            $forward = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}?page=" .($this->currentPage + 1). '#comments">&gt;</a></li>';
        }
        if( $this->currentPage > 3 ){
            $startpage = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}#comments\">&laquo;</a></li>";
        }
        if( $this->currentPage < ($this->countPages - 2) ){
            $endpage = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}?page={$this->countPages}#comments\">&raquo;</a></li>";
        }
        if( $this->currentPage - 2 > 0 ){
			if($this->currentPage == 3){
				$page2left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}#comments\">" .($this->currentPage - 2). '</a></li>';
			}else{
				$page2left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}?page=" .($this->currentPage-2). '#comments">' .($this->currentPage - 2). '</a></li>';
			}
        }
        if( $this->currentPage - 1 > 0 ){
			if($this->currentPage == 2){
				$page1left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}#comments\">" .($this->currentPage-1). '</a></li>';
			}else{	
				$page1left = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}?page=" .($this->currentPage-1). '#comments">' .($this->currentPage-1). '</a></li>';
			}
        }
        if( $this->currentPage + 1 <= $this->countPages ){
            $page1right = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}?page=" .($this->currentPage + 1). '#comments">' .($this->currentPage+1). '</a></li>';
        }
        if( $this->currentPage + 2 <= $this->countPages ){
            $page2right = "<li><a itemprop=\"url\" class=\"nav-link\" href=\"{$this->uri}?page=" .($this->currentPage + 2). '#comments">' .($this->currentPage + 2). '</a></li>';
        }
		
		$out = '';
		if($this->countPages > 1){
			$out = "<ul itemscope itemtype=\"http://schema.org/SiteNavigationElement/Pagination\" class=\"pagination\">$startpage$back$page2left$page1left<li class=\"active\"><a itemprop=\"url\">{$this->currentPage}</a></li>$page1right$page2right$forward$endpage</ul>";
		}
		
		return $out;
    }

    public function getParams(){
        $url = $_SERVER['REQUEST_URI'];
        $url = explode('?', $url);
        $uri = $url[0];
        if(isset($url[1]) && $url[1] != ''){
            $params = explode('&', $url[1]);
            foreach($params as $param){
                if(!preg_match("#page=#", $param)) $uri .= "{$param}&amp;";
            }
        }
        return urldecode($uri);
    }

}