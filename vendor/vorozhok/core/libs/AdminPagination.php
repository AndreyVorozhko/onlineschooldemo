<?php

namespace vorozhok\libs;

class AdminPagination extends Pagination{

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
				$back = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}\">&lt;</a></li>";
				
			}else{
				$back = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/" .($this->currentPage - 1). '>&lt;</a></li>';
			}
        }
        if( $this->currentPage < $this->countPages ){
            $forward = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/" .($this->currentPage + 1). '">&gt;</a></li>';
			
        }
        if( $this->currentPage > 3 ){
            $startpage = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/" .($this->currentPage + 1). '">&laquo;</a></li>';
        }
        if( $this->currentPage < ($this->countPages - 2) ){
            $endpage = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/{$this->countPages}\">&raquo;</a></li>";
        }
        if( $this->currentPage - 2 > 0 ){
			if($this->currentPage == 3){
				$page2left = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}\">" .($this->currentPage - 2). '</a></li>';
			}else{
				$page2left = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/" .($this->currentPage-2). '">' .($this->currentPage - 2). '</a></li>';
			}
        }
        if( $this->currentPage - 1 > 0 ){
			if($this->currentPage == 2){
				$page1left = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}\">" .($this->currentPage-1). '</a></li>';
			}else{
				$page1left = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/" .($this->currentPage-1). '">' .($this->currentPage-1). '</a></li>';
			}
        }
        if( $this->currentPage + 1 <= $this->countPages ){
            $page1right = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/" .($this->currentPage + 1). '">' .($this->currentPage+1). '</a></li>';
        }
        if( $this->currentPage + 2 <= $this->countPages ){
            $page2right = "<li class=\"page-item\"><a class=\"page-link\" href=\"{$this->uri}page/" .($this->currentPage + 2). '">' .($this->currentPage + 2). '</a></li>';
        }
		
		$out = '';
		
		if($this->countPages > 1){
		$out = "<ul class=\"pagination pagination-sm m-0 float-right\">$startpage$back$page2left$page1left<li class=\"active\"><a class=\"page-link\">{$this->currentPage}</a></li>$page1right$page2right$forward$endpage</ul>";
		}

        return $out;
    }

}