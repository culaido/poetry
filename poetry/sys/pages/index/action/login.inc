<?php

	$token	 = cookie::get('token', 'string');
	$account = cookie::get('account', 'string');

	if ( $token ) {

		$user = lib::checkEmail($account)
			? db::fetch('SELECT * FROM user WHERE email=?',   array($account))
			: db::fetch('SELECT * FROM user WHERE account=?', array($account));

		if ( $user['status'] == self::verified ) {

			$token2 = md5("{$user['account']}|{$user['password']}");

			if ( $token == $token2 ) {

				db::query('UPDATE user SET lastLoginTime=NOW(), loginTimes=loginTimes+1 WHERE id=?', array($user['id']));

				cookie::set('account', $account, time()+3600*24*30);
				cookie::set('token',   $token,	 time()+3600*24*30);

				log::save('user', 'login', $user['id']);
				
				profile::setSession( $user['id'] );

				url::redir( WEB_ROOT . '/index/');
				return;
			}
		}
	}

	$next = args::get("next", "string");

	$this->setLoginNext($next);


	$this->form[] = CForm::icoFieldset(
		'user',
		CForm::text($this, 'account', $account,
			array('required', 'minLength' => 3),
			array('style'=>'width:100%', 'hideRequired'=>true, 'item-name'=>_T('account'), 'placeholder'=>'Account or E-mail'))
	);

	$this->form[] = CForm::icoFieldset(
		'lock',
		CForm::password($this, 'password', '',
		array('required', 'minLength' => 3),
		array('style' => 'width:100%', 'hideRequired' => true, 'item-name'=>_T('password'), 'placeholder'=>'Password'))
	);

	$p = array(
		'<div style="cursor:pointer; font-size:12px; margin-top:-3px" class="pull-left">' . CForm::checkbox($this, 'rememberMe', 1, array(), array('title' => _T('user-rememberMe'))) . '</div>',
		theme::a( _T('register'), WEB_ROOT . '/index/register' ),
		CForm::addSubmit()
	);

	$this->form[] = CForm::fieldset('',
		'<div class="clearfix text-right">'
			. join('&nbsp;&nbsp;&nbsp;', $p) .
		'</div>'
	);

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> _T('login'),
			'layout'	=> 'login',
			'template'	=> 'login'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
	$this->doc = &$this->App->DOC->get('page.content');
