<?php

	$this->breadcrumb = array();

	$this->breadcrumb[] = array('title' => _T('playlist'), 'curr' => true);

	$this->doc['title'] = _T('playlist');

	$this->order		= args::get('order',		'white_list', array('id', 'poetry', 'title', 'lastUpdate'));
	$this->precedence	= args::get('precedence',	'white_list', array('DESC', 'ASC'));

	$this->playlists = $this->getList( array(
		'order' 	 => $this->order,
		'precedence' => $this->precedence
	) );

	$data = array(
		'type' => 'html',
		'html' => array(
			'layout'	=> array(

				'mbox' => array(
					array('mod_playlist'	=> array('hotest'	=> array('limit' => 50)))
				),

				'xbox' => array(
					array('playlist'	=> array('breadcrumb'	=> array())),
					array('playlist'	=> array('list'			=> array()))
				)
			),
			'template'	=> 'playlist'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);

	js::add(<<<js

		$(document).ready(function() {
			$('[data-toggle=offcanvas]').click(function() {
				$('.row-offcanvas').toggleClass('active');
			});

		});
js
);