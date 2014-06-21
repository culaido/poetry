<?php
	$this->form[] = CForm::icoFieldset(
		'user',
		CForm::text($this, 'account', '',
		array('required', 'minLength' => 3),
		array('style' => 'width:100%', 'hideRequired'=>true, 'item-name'=>_T('account'), 'placeholder'=>_T('account')))
	);
	
	$this->form[] = CForm::icoFieldset(
		'lock',
		CForm::text($this, 'password', '',
		array('required', 'minLength' => 3),
		array('style' => 'width:100%', 'hideRequired'=>true, 'item-name'=>_T('password'), 'placeholder'=>_T('password')))
	);

	$this->form[] = CForm::icoFieldset(
		'smile-o',
		CForm::text($this, 'name', '',
		array('required'),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('name'), 'placeholder'=>_T('name'))). '<div class="text-right hint">' . _T('user-useRealName') . '</div>'
	);

	$this->form[] = CForm::icoFieldset(
		'phone',
		CForm::text($this, 'phone', '',
		array(),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('phone'), 'placeholder'=>_T('phone')))
	);

	$this->form[] = CForm::icoFieldset(
		'envelope',
		CForm::text($this, 'email', '',
		array('required', 'email'),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('email'), 'placeholder'=>_T('email')))
	);
	
	
	$role = Role::get();

	$roleIco = array();
	foreach ( $role as $v ) {
		$option[] = array(
			'value'		=> $v['id'], 
			'text'		=> lib::htmlChars( $v['name'] ), 
			'selected'	=> ($v['id'] == _ROLE::DEFAULT_ROLE) ? true : false 
		);		
	}	
	
	$this->form[] = CForm::hFieldset(
		_T('role'),
		CForm::select($this, 'role', $option,  array(), array('style'=>'width:auto'))
	);


	$this->form[] = CForm::fieldset('',
		'<div class="text-right">
			<div class="text-right">'
				. CForm::addSubmit() .
			'</div>
		</div>'
	);

	$data = array(
		'type' => 'html',
		'html' => array(
			'layout'	=> 'add',
			'template'	=> 'plain'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
	$this->doc = &$this->App->DOC->get('page.content');
