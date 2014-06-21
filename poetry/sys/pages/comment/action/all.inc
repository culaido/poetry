<?php

	$this->breadcrumb = array();

	$this->breadcrumb[] = array('title' => _T('folder'), 'curr' => true);

	$this->doc['title'] = _T('folder');

	$this->order		= args::get('order',		'white_list', array('id', 'poetryCnt', 'title', 'lastUpdate'));
	$this->precedence	= args::get('precedence',	'white_list', array('DESC', 'ASC'));

	$this->folders = prog::get('folder')->getList( array(
		'order' 	 => $this->order,
		'precedence' => $this->precedence
	) );

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> _T('siteName'),
			'layout'	=> array(

				'mbox' => array(
					array('mod_poetry'	=> array('hotest'	=> array('limit' => 10))),
					array('mod_poetry'	=> array('newest'	=> array('limit' => 10)))
				),

				'xbox' => array(
					array('folder'		=> array('breadcrumb'	=> array())),

					array('folder'		=> array('title'	=> array())),
					array('folder'		=> array('list'		=> array()))
				)
			),
			'template'	=> 'folder'
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