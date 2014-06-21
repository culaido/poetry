<?php

Class mod_search Extends Module {

	public function ac_helper(){}

	public function vw_helper(){

		$this->keyword = args::get('keyword', 'nothing');

		$keyword = lib::htmlChars( $this->keyword );
		$js =<<<search
			$(function(){
				var keyword = '{$keyword}';
				$("[role='searcher']").val(keyword);
			});

			function searchOnKeypress(e){
				var keyCode = (window.event) ? e.keyCode : e.keyCode;

				if ( keyCode == 13 ) search();
			}

			function search(){
				$.Evt('poetry.search').publish( $('[role="searcher"]').val() );
			}
search;
		js::add( $js );

		echo theme::block( _T('search'),
			'<div class="clearfix" style="position:relative">
				<button onclick="search()" class="pull-right" type="button" style="width:50px; overflow:hidden">' . _T('search') . '</button>
				<input role="searcher" onkeypress="searchOnKeypress(event)" type="search" style="position:absolute; top:0; left:0; right:55px" placeholder="' . _T('search no, title, lyric') . '"/>
			</div>'
		);
	}


}