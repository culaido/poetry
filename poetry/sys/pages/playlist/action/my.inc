<?php
	js::add(<<<js

		$(document).ready(function() {
			$('[data-toggle=offcanvas]').click(function() {
				$('.row-offcanvas').toggleClass('active');
			});
		});
js
);
	$this->playlist = $this->get(profile::$id, 'creator');
	krsort( $this->playlist );

	$data = array(
		'type' => 'html',
		'html' => array(
			'layout'	=> 'my',
			'template'	=> 'playlist'
		),
		'content' => array()
	);
	

	$this->App->DOC->set('page', $data);
