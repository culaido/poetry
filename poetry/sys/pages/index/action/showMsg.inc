<?php

	$data = array(
		'type'  => 'html',
		'html'  => array(
			'title' => _T('register'),
			'layout'   => array(
				'xbox' => array(
					array('index' => array('showMsg'=> '')))
				),
			'template' => 'login'
		),
		'content'  => array()
	);


	$this->App->DOC->set('page', $data);

	$this->doc = &$this->App->DOC->get('page.content');