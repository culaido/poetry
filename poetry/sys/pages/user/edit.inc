<?php

	$this->form[] = CForm::icoFieldset(
		'user',
		CForm::text($this, 'account', profile::$account,
		array('required', 'minLength' => 3),
		array('disabled' => true, 'style' => 'width:100%', 'hideRequired'=>true, 'item-name'=>_T('account'), 'placeholder'=>_T('account')))
	);

	$this->form[] = CForm::icoFieldset(
		'smile-o',
		CForm::text($this, 'name', profile::$name,
		array('required'),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('name'), 'placeholder'=>_T('name'))). '<div class="text-right hint">' . _T('user-useRealName') . '</div>'
	);

	$this->form[] = CForm::icoFieldset(
		'envelope',
		CForm::text($this, 'email', profile::$email,
		array('required', 'email'),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('email'), 'placeholder'=>_T('email')))
	);

	$p = array(
		CForm::addSubmit()
	);

	$this->form[] = CForm::fieldset('',
		'<div class="text-right">
			<div class="text-right">'
				. CForm::addSubmit() .
					'&nbsp;&nbsp;&nbsp;'
				. CForm::addCancel() .
			'</div>
		</div>'
	);

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> _T('register'),
			'layout'	=> 'edit',
			'template'	=> 'login'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
	$this->doc = &$this->App->DOC->get('page.content');
