<?php

	$this->_id = args::get('id', 'int');

	$this->doc = array_shift( $this->get($this->_id) );

	CForm::hidden($this, 'id', $this->_id, array('readonly'));

	$this->form[] = CForm::icoFieldset(
		'pencil',
		CForm::text($this, 'title', $this->doc['title'],
		array('required'),
		array('hideRequired'=>true, 'item-name'=>_T('folder-title'), 'placeholder'=>_T('folder-title')))
	);

	$this->form[] = CForm::fieldset(_T('poetry-note'), CForm::editor( $this, 'note', $this->doc['note'] ) );

	$this->form[] = CForm::fieldset('',
		'<div class="text-right">'
			. CForm::addSubmit() .
				'&nbsp;&nbsp;&nbsp;'
			. CForm::addCancel() .
		'</div>'
	);

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> _T('add'),
			'layout'	=> 'edit',
			'template'	=> 'plain'
		),
		'content' => array()
	);

	$js =<<<js

		$(function(){
			$('body').off('click', 'button[data-role="form-cancel"]');

			$('body').on('click', 'button[data-role="form-cancel"]', function(){
				parent.ui.modal.close();
			});
		});
js;

	js::add( $js );
	$this->App->DOC->set('page', $data);
	$this->doc = &$this->App->DOC->get('page.content');
