<?php

	js::add(<<<js

		$(document).ready(function() {
			$('[data-toggle=offcanvas]').click(function() {
				$('.row-offcanvas').toggleClass('active');
			});
		});
js
);
	$this->playlist = array_shift( $this->get( $this->_id ) );

	$this->user		= array_shift( prog::get('user')->get( $this->playlist['creator'], 'id' ) );

	$this->breadcrumb = array();
	$this->breadcrumb[] = array('title' => theme::a( lib::htmlChars( _T('playlist') ), $this->getLink('all') ));

	$this->_form['id']	 = CForm::hidden($this, 'id', $this->_id);

	$items = db::select('SELECT * FROM playlist_item WHERE parentID=:parentID ORDER BY sn ASC', array(
		':parentID' => $this->_id
	));

	$this->items = array();

	$notationObj = prog::get('mod_notation');
	$poetry = $this->notation = array();

	while ( $obj = $items->fetch() ) {
		$this->items[] = $obj;

		list( $page, $id ) = lib::parsePageId($obj['pageId']);
		$poetry[ $id ] = $id;

		$this->notation[ $id ][ $obj['type'] ] = $notationObj->get( $obj['pageId'], $obj['type'] );
	}

	$this->poetry = prog::get('poetry')->get( $poetry );

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> $this->playlist['title'],
			'layout'	=> 'base',
			'template'	=> 'playlist'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);

	css::addFile('http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css');
	css::addFile(CSS_PATH . '/bootstrap-image-gallery.min.css');

	if ($_SESSION['view']['playlist'][$this->_id] != 1) {
		$_SESSION['view']['playlist'][$this->_id] = 1;
		db::query('UPDATE LOW_PRIORITY playlist SET view=view+1 WHERE id=?', array($this->_id));
		$this->doc['view'] += 1;
	}
