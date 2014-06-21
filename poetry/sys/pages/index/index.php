<?php

class index Extends Page {

	const suspend	= 0;
	const notAuth	= 1;
	const verified	= 2;

    private $doc;

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

	function priv($param, $action) {

		if ( !profile::$id ) {

			if ( in_array( $action, array('showMsg') ) ) {
				return $action;
			}

			if ( sys::getCfg('sys_allowAnonymous', '0') == '0' ) {

			 	if ( in_array( $action, array('register', 'sendAuth', 'login' ) ) ) {

					return $action;

				} else {
			 		url::redir( WEB_ROOT . '/index/login');
				}
			}

		} else {

			if ( in_array( $action, array('login', 'register') ) ) {
				url::redir( WEB_ROOT . '/');
			}
		}

		return $action;
	}

    function exec($param, $action) {

		if ( $action == '' ) $action = 'base';
		$action_file = PAGE_PATH . "/index/action/{$action}.inc";

		if (file_exists($action_file)) require($action_file);
	}


	function formValid(){

		if (self::$_pageMode == 'register') {

			$password1 = $this->getArgs('password1', 'string');
			$password2 = $this->getArgs('password2', 'string');

			if ( $password1 != $password2 )
				$ret[] = $this->formReturn('password1', _T('passwordCfFail'));

			$userObj = prog::get('user');

			$account = $this->getArgs('account', 'string');
			$exist	 = array_shift( $userObj->get($account, 'account') );

			if ( $exist ) $ret[] = $this->formReturn('account', _T('accountExist'));

			$email = $this->getArgs('email', 'string');
			$exist = array_shift( $userObj->get($email, 'email') );

			if ( $exist ) $ret[] = $this->formReturn('email', _T('emailExist'));

			prog::get('mgr')->mgrItemClac();

			return $ret;
		}
	}

	function formSave(){

		if (self::$_pageMode == 'login') {

			$account	= $this->getArgs('account',  'nothing');
			$password	= $this->getArgs('password', 'nothing');

			$user = lib::checkEmail($account)
				? db::fetch('SELECT * FROM user WHERE email=?',   array($account))
				: db::fetch('SELECT * FROM user WHERE account=?', array($account));

			if ( !$user ) {
				echo $this->ajaxReturn(false, _T('accountNotExist'), 'account');
				exit;
			}

			if ( md5($password) != $user['password'] ) {
				echo $this->ajaxReturn(false, _T('passwordWrong'), 'password');
				exit;
			}

			switch ( $user['status'] )
			{
				case self::suspend:
					echo $this->ajaxReturn(false, _T('user-suspend'));
					exit;

				case self::notAuth:
					echo $this->ajaxReturn(false, _T('user-notAuth'), 'password');
					exit;

				default: break;
			}

			$timezone = cookie::get('timezone', 'string');
			$timezone = ($timezone>=(-12) && $timezone<=(+13)) ? $timezone : 0;

			db::query('UPDATE user SET lastLoginTime=NOW(), loginTimes=loginTimes+1, timezone=? WHERE id=?', array($timezone, $user['id']));

			profile::setSession( $user['id'] );

			log::save('user', 'login', $user['id']);
			$_SESSION['loginSince'] = time();

			$next = $this->getArgs('next', 'string');

			if (substr($next, 0, 7)=='http://'
				|| substr($next, 0, 8)=='https://'
				|| substr($next, 0, 2)=='//'
			   )
				$next = '/';
				
			if ( $this->getArgs('rememberMe', 'int') == 1 ){
				
				$token = md5("{$user['account']}|{$user['password']}");

				// remember me for 30 days
				cookie::set('account', $user['account'], time()+3600*24*30);
				cookie::set('token',   $token,			 time()+3600*24*30);
			}

			CForm::onSuccess('redir', $next);

			return;
		}

		if (self::$_pageMode == 'register') {

			$data = array(
				'account'     => $this->getArgs('account',		'string'),
				'password'    => $this->getArgs('password1',	'string'),
				'name'        => $this->getArgs('name',			'string'),
				'email'       => $this->getArgs('email',		'string'),
				'phone'       => $this->getArgs('phone',		'string'),
				'status'      => self::notAuth
			);

			$userObj = prog::get('user');
			$userObj->create($data);

			lib::mail('culaido@gmail.com', 'register poetry', $data['account'] . ' - ' . $data['name']);
			CForm::onSuccess('redir', WEB_ROOT . "/index/showMsg/?msg=regdone_auth&next=" . urlencode($next));

			return;
		}
	}


	function vw_showMsg(){

		$next = args::get('next', 'string', '/');
		$msg  = args::get('msg', 'string');

		echo "<div class='panel'>" . theme::block(_T($msg . '-title'), _T($msg . '-content')) . "</div>";
	}


	function vw_login(){

		echo
			'<div class="panel">' .
				theme::block(
					_T('Member Login'),
					'<div class="form">' . join('', $this->form) . '</div>'
				) .
			'</div>';


		CForm::submit( WEB_ROOT . '/index/login', '', 'login', lib::makeAuth( array( WEB_ROOT . '/index/login', 'login') ));
		CForm::render();

	}


	function vw_register(){

		echo
			'<div class="panel">' .
				theme::block(
					_T('Member Register'),
					'<div class="form">' . join('', $this->form) . '</div>'
				) .
			'</div>';


		CForm::submit( WEB_ROOT . '/index/register', '', 'register', lib::makeAuth( array( WEB_ROOT . '/index/register', 'register') ));
		CForm::render();
	}


	function vw_info(){

		require_once ROOT_PATH . "/site/extra.php";
		$info = $indexInfo;

		echo theme::block('', "
			<div class='clearfix'>
				<div class='col-md-6 text-center'>
					<img src='" . ICON_PATH . "/index/01.png' class='img-circle' />
					<h2>"	. lib::htmlChars($info[1]['title']) . "</h2>
					<p>"	. $info[1]['info']	. "</p>
				</div>

				<div class='col-md-6 text-center'>
					<img src='" . ICON_PATH . "/index/02.png' class='img-circle' />
					<h2>"	. lib::htmlChars($info[2]['title']) . "</h2>
					<p>"	. $info[2]['info']	. "</p>
				</div>			</div>
		");

	}

    public function setLoginNext($next) {

    	if(empty($next)) {

    		// if session not exist then check the profile
    		if(empty($_SESSION['login.next'])) {
    			$_SESSION['login.next'] = "/";
    		} // do nothing if session exist and not empty

    	} else { // override session
    		$_SESSION['login.next'] = $next;
    	}
    }

    public function getLoginNext(){
    	return $_SESSION['login.next'];
    }

    public function unsetLoginNext(){
    	unset($_SESSION['login.next']);
    }






}