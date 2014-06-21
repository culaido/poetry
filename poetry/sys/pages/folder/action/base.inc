<?php

	if ( $this->_id == 0 ) {
		$this->doc = array(
			'title' => _T('folder-root')
		);
	} else {
		$this->doc = array_shift( $this->get( $this->_id ) );
	}

	$this->breadcrumb = array();

	$this->breadcrumb[] = array('title' => _T('folder'),		'link' => $this->getLink('all'));
	$this->breadcrumb[] = array('title' => $this->doc['title'], 'curr' => true);

	$data = array(
		'type' => 'html',
		'html' => array(
			'layout'	=> array(

				'mbox' => array(
					array('mod_folder'	=> array('list'		=> array('focus' => $this->_id))),
					array('mod_poetry'	=> array('hotest'	=> '')),
					array('mod_poetry'	=> array('newest'	=> ''))
				),

				'xbox' => array(
					array('folder'		=> array('breadcrumb'	=> array())),

					array('folder'		=> array('title'	=> array())),
					array('mod_poetry'	=> array('list'		=> array('folderID' => $this->_id)))
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