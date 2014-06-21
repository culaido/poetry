<?php

	$this->pageId = args::get('pageId', 'nothing');
	list($page, $id) = lib::parsePageId($this->pageId);

	$this->poetry = array_shift( prog::get('poetry')->get( $id ) );

	if ( !$this->poetry ) {
		return;
	}

	$this->notation = prog::get('mod_notation')->getUsable( $this->pageId );

	$this->_form['pageId']		= CForm::hidden($this, 'pageId',		$this->pageId);
	$this->_form['playlistId']	= CForm::hidden($this, 'playlistId',	'', array(), array());

	if ( count( $this->notation ) == 0 ) {
		CForm::hidden($this, 'type', 1);
	} else { 
	
		foreach ( $this->notation as $k=>$v ){
			$opt[] = array('value' => $k, 'title' => _T('notation-type' . $k));
		}

		$opt[0]['checked'] = true;

		$this->form['poetry'] = CForm::fieldset( _T('poetry') . ' : ' . lib::htmlChars( $this->poetry['title'] ),
			'<span style="margin-right:20px">' . _T('playlist-addNotationType') . '</span>', CForm::radios($this, 'type', $opt)
		);
	}
	
	
	$this->playlist = $this->get( profile::$id, 'creator' );
	krsort( $this->playlist );
	
	$items = db::select('SELECT * FROM playlist_item WHERE creator=:creator AND pageId=:pageId', array(
		':creator'	=> profile::$id,
		':pageId'	=> $this->pageId
	));
	
	$this->status = array();
	
	foreach ( $items as $v ){
		$this->status[ $v['parentID'] ][ $v['type'] ] = $v;	
	}

	$this->form['creator'] = CForm::fieldset( '',
		CForm::checkbox($this, 'createNew', 1, array(), 
			array(
				'checked'	=> ( count($this->playlist) == 0 ) ? true: false,
				'title'		=> _T('playlist-addItemToNewList'))
			),

		CForm::icoFieldset(
			'pencil',
			CForm::text($this, 'title', '',
			array(),
			array('disabled' => true, 'style' => 'width:400px', 'item-name'=>_T('playlist-title'), 'placeholder'=>_T('playlist-title')))
		) .
		
		CForm::checkbox($this, 'priv', 1, array(), array('title' => _T('playlist-privPS'), 'checked' => false ))
	);

	$data = array(
		'type' => 'html',
		'html' => array(
			'layout'	=> 'addItem',
			'template'	=> 'plain'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
