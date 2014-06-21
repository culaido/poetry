<?php

	$this->playlist = array_shift( $this->get( $this->_id ) );

	if ( $this->playlist['creator'] != profile::$id ) return;

	$this->_form['id'] = CForm::hidden($this, 'id', $this->_id);

	$this->form[] = CForm::fieldset(
		_T('playlist-title'),
		CForm::text($this, 'title', $this->playlist['title'],
			array('required'),
			array('autofocus' => 'true', 'item-name'=>_T('playlist-title'))) .
	
		CForm::checkbox($this, 'priv', 1, array(), array('title' => _T('playlist-privPS'), 'checked' => ($this->playlist['priv'] == 1) ? true : false ))
	);

	$this->form[] = CForm::fieldset(' ', CForm::addSubmit()	);


	$items = db::select('SELECT * FROM playlist_item WHERE parentID=:parentID ORDER BY sn ASC', array(
		':parentID' => $this->_id
	));

	$this->items = array();

	$poetry = $this->notation = array();

	while ( $obj = $items->fetch() ) {
		$this->items[] = $obj;

		list( $page, $id ) = lib::parsePageId($obj['pageId']);
		$poetry[ $id ] = $id;
	}

	$this->poetry = prog::get('poetry')->get( $poetry );


	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> $this->playlist['title'],
			'layout'	=> 'edit',
			'template'	=> 'plain'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
