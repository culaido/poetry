<?php

	$this->form[] = CForm::icoFieldset(
		'user',
		CForm::text($this, 'account', '',
		array('required', 'minLength' => 3),
		array('style' => 'width:100%', 'hideRequired'=>true, 'item-name'=>_T('account'), 'placeholder'=>_T('account')))
	);

	$this->form[] = CForm::icoFieldset(
		'lock',
		CForm::password($this, 'password1', '',
		array('required', 'minLength' => 3),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('password'), 'placeholder'=>_T('password')))
	);

	$this->form[] = CForm::icoFieldset(
		'lock',
		CForm::password($this, 'password2', '',
		array('required', 'minLength' => 3),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('passwordCF'), 'placeholder'=>_T('passwordCF')))
	);

	$this->form[] = CForm::icoFieldset(
		'smile-o',
		CForm::text($this, 'name', '',
		array('required'),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('name'), 'placeholder'=>_T('name'))) . '<div class="text-right hint">' . _T('user-useRealName') . '</div>'
	);

	$this->form[] = CForm::icoFieldset(
		'envelope',
		CForm::text($this, 'email', '',
		array('required', 'email'),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('email'), 'placeholder'=>_T('email')))
	);

	$this->form[] = CForm::icoFieldset(
		'phone',
		CForm::text($this, 'phone', '',
		array(),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('phone'), 'placeholder'=>_T('phone')))
	);

	$p = array(
		theme::a( _T('returnLogin'), WEB_ROOT),
		CForm::addSubmit()
	);

	$this->form[] = CForm::fieldset('',
		'<div class="text-right">'
			. join('&nbsp;&nbsp;&nbsp;', $p) .
		'</div>'
	);

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> _T('register'),
			'layout'	=> 'register',
			'template'	=> 'login'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
	$this->doc = &$this->App->DOC->get('page.content');
