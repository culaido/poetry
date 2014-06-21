<?php

Class mod_carousel Extends Module {

	function ac_show(){

		$this->doc = array(
			WEB_ROOT . '/' . MODULE_PATH . '/mod_carousel/pic/02.jpg',
			WEB_ROOT . '/' . MODULE_PATH . '/mod_carousel/pic/03.jpg'
		);
	}

	function vw_show(){

		foreach ($this->doc as $k=>$v){

			$act = ($k == 0) ? 'active' : '';
			$indicators[]	= "<li data-target='#carousel' data-slide-to='{$k}' class='{$act}'></li>";
			$item[]			= "<div class='item {$act}' ><img src='{$v}' /></div>";
		}

		$js =<<<js

			$(function(){
				$('.carousel').carousel({
					interval : 10000
				});
			});
js;

		js::add( $js );


		echo theme::block('', '
			<div id="carousel" class="carousel slide" data-ride="carousel" style="border:1px solid #333; padding:1px">

				<ol class="carousel-indicators">
					' . join('', $indicators) . '
				</ol>

				<div class="carousel-inner">
					' . join('', $item) . '
				</div>

				<a class="left carousel-control" href="#carousel" data-slide="prev">
					<span class="glyphicon glyphicon-chevron-left"></span>
				</a>
				<a class="right carousel-control" href="#carousel" data-slide="next">
					<span class="glyphicon glyphicon-chevron-right"></span>
				</a>
			</div>'
		);





	}

}