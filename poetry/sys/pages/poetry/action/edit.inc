<?php

	$this->_form['from'] = CForm::hidden($this, 'from', base64_encode(args::get('from', 'nothing')));
	$this->_form['id']	 = CForm::hidden($this, 'id', $this->_id);

	$this->doc		= array_shift( $this->get( $this->_id ) );
	$this->tonality = $this->getTonality();

	$this->form['left']['no'] = CForm::hFieldset(
		_T('poetry-no'),
		CForm::text($this, 'no', ( $this->doc['no'] == 0 ) ? '' : $this->doc['no'],
			array('number'),
			array('style' => 'width:100px', 'item-name'=>_T('poetry-no'))
		)
	);

	$this->form['left']['title'] = CForm::hFieldset(
		_T('poetry-title'),
		CForm::text($this, 'title', $this->doc['title'],
			array('required'),
			array('autofocus' => 'true', 'item-name'=>_T('poetry-title')))
	);

	$this->form['left']['lyric'] = CForm::hFieldset(
		_T('poetry-lyric'),
		CForm::textarea($this, 'lyric', $this->doc['lyric'],
			array(),
			array('item-name'=>_T('poetry-lyric')))
	);

	if ( defined('EMBEDLY_KEY') ) {

		$this->form['left']['url'] = CForm::hFieldset(
			_T('poetry-media'),
			CForm::text($this, 'url', $this->doc['url'], array('url'), array('item-name'=>_T('poetry-media'), 'placeholder'=>_T('url')))
		);
	}

	$option = array();

	foreach ($this->tonality as $v){

		$option[] = array(
			'value'		=> $v['id'],
			'text'		=> lib::htmlChars( $v['title'] ),
			'selected'	=> ($this->doc['tonality'] == $v['id']) ? true : false
		);
	}

	$this->form['right']['tonality'] = CForm::hFieldset(
		_T('poetry-tonality'),

		CForm::hBox(
			CForm::select($this, 'tonality', $option,  array(), array('style'=>'width:80px')),
			CForm::text($this, 'transposition', $this->doc['transposition'],
				array(),
				array('placeholder'=>_T('poetry-transposition'), 'style'=>'margin-left:20px; width:150px'))
		)
	);

	$folders = prog::get('folder')->getList();

	$folders = array( 0 => array(
		'id'	=> 0,
		'title' => _T('folder-root')
	)) + $folders;

	$option = array();

	foreach ($folders as $v){

		$no = ( $v['no'] ) ? $v['no'] . '. ' : '';
		$title = ( $v['id'] == 0 )
			? lib::htmlChars( $v['title'] )
			: '&nbsp;&raquo;&nbsp;' . $no . lib::htmlChars( $v['title'] );

		$option[] = array(
			'value'		=> $v['id'],
			'text'		=> $title,
			'selected'	=> ($this->doc['folderID'] == $v['id']) ? true : false
		);
	}

	$this->form['right']['folderID'] = CForm::hFieldset(
		_T('folder'),
		CForm::select($this, 'folderID', $option)
	);

	$this->form['note'] = CForm::fieldset(
		_T('poetry-note'),
		CForm::editor( $this, 'note', $this->doc['note'] )
	);

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> _T('poetry-edit') . ' - ' . $this->doc['title'],
			'layout'	=> 'edit',
			'template'	=> 'poetry.edit'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
