<?php

Class user Extends Page {

	const suspend	= 0;
	const notAuth	= 1;
	const verified	= 2;

	function priv($param, $action) {

		if ( $action == 'edit' ) {
			url::redir( $this->getEditLink( profile::$id, url::get() ) );
			return;
		}

		return $action;
	}

	function exec($param, $action) {

		
		if ( $action == 'add' ) {
			require("action/add.inc");
			return;
		}


		$this->_id = $param[0];

        if ( self::$_pageMode == 'edit' ) {
			require("action/edit.inc");  // include file to prepare page data
			
        }
	}

	function formValid(){

		if (self::$_pageMode == 'edit') {

			$email	= $this->getArgs('email', 'string');
			$user	= array_shift( $this->get($email, 'email') );

			if ( $user && profile::$id != $user['id'] )
				$ret[] = $this->formReturn('email', _T('emailExist'));

			return $ret;
		}

		if (self::$_pageMode == 'add') {

			$userObj = prog::get('user');

			$account = $this->getArgs('account', 'string');
			$exist	 = array_shift( $this->get($account, 'account') );

			if ( $exist ) $ret[] = $this->formReturn('account', _T('accountExist'));

			$email = $this->getArgs('email', 'string');
			$exist = array_shift( $this->get($email, 'email') );

			if ( $exist ) $ret[] = $this->formReturn('email', _T('emailExist'));

			return $ret;
		}
	}


	function formSave(){


		if (self::$_pageMode == 'edit') {

			$data = array(
				'name'	=> $this->getArgs('name',	'string'),
				'email'	=> $this->getArgs('email',	'string'),
				'phone'	=> $this->getArgs('phone',	'string')
			);

			$this->save( profile::$id, $data );

			profile::setSession( profile::$id );

			CForm::onSuccess('redir', WEB_ROOT);
			return;
		}

		if (self::$_pageMode == 'add') {

			$data = array(
				'account'	=> $this->getArgs('account',	'string'),
				'password'	=> $this->getArgs('password',	'string'),
				'name'		=> $this->getArgs('name',	'string'),
				'email'		=> $this->getArgs('email',	'string'),
				'phone'		=> $this->getArgs('phone',	'string'),
				'role'		=> $this->getArgs('role',	'nothing'),
				'status'	=> self::verified,
				'verifiedBy'=> profile::$id
			);

			$this->create( $data );
			CForm::onSuccess('redir', 'parentReload');

			return;
		}
	}


	public function vw_add(){
		echo  '<div style="width:530px">' . theme::block('',join('', $this->form)) . '</div>';


		CForm::submit( WEB_ROOT . '/user/add', '', 'add', lib::makeAuth( array( WEB_ROOT . '/user/add', 'add') ));
		CForm::render();
	}

	public function vw_edit(){

		echo
			'<div class="panel">' .
				theme::block(
					_T('Profile Edit'),
					'<div class="form">' . join('', $this->form) . '</div>'
				) .
			'</div>';
	}

	public function create($param){

		if ( !$param['account'] || !$param['password'] || !$param['email'] ) return FALSE;

		if ( !lib::checkEmail($param['email']) ) return FALSE;

		$exist = db::fetch('SELECT COUNT(*) AS cnt FROM user WHERE account=:account OR email=:email', array(
			':account'	=> $param['account'], ':email' => $param['email']
		));

		if ( $exist['cnt'] > 0 ) return FALSE;

		db::query('INSERT INTO user SET account=:account, email=:email', array(
				':account'	=> $param['account'],
				':email'	=> $param['email']
			));

		$id = db::lastInsertId();

		$param['id'] = $id;

		$this->save($id, $param);

		return array('status' => TRUE, 'data' => $param);
	}

	public function save($id, $param){

		$data = array();

		if ($param['password']) $param['password'] = md5( $param['password'] );

        foreach ($param as $idx=>$val){

            if (in_array($idx, array('id', 'account'))) continue;

            $data['qry'][]   = "{$idx}=:{$idx}";
            $data['param'][":{$idx}"] =  $val;
        }

        $data['param'][':id'] = $id;

        db::query("UPDATE user SET " . join(', ', $data['qry']) . "  WHERE id=:id", $data['param']);

        return array('status' => TRUE);
	}

	public function getCnt(){

		$total		= db::fetch('SELECT COUNT(*) AS cnt FROM user WHERE status!=?', array(self::suspend));
		$notAuth	= db::fetch('SELECT COUNT(*) AS cnt FROM user WHERE status=?',  array(self::notAuth));
		$verified	= db::fetch('SELECT COUNT(*) AS cnt FROM user WHERE status=?',  array(self::verified));

		return array('total' => $total['cnt'], 'notAuth' => $notAuth['cnt'], 'verified' => $verified['cnt']);
	}

	private $_getInfo;
	public function get($id, $type = 'id')	{
        if (!is_array($id)) $id = array($id);

        if (count($id)==1 && $this->_getInfo[$type][$id[0]]){
            return array($id[0] => $this->_getInfo[$type][$id[0]]);
        }

		$user = db::select("SELECT * FROM user WHERE FIND_IN_SET({$type}, ?)", array(join(',', $id)));

        $ret = array();
		while ($obj = $user->fetch()) {
			$this->_getInfo[$type][$obj['id']] = $obj;
            $ret[ $obj['id'] ] = $this->_getInfo[$type][$obj['id']];
		}

		return $ret;
	}

	function isReserve($account) {
		return array('status' => TRUE);
	}


	function getAddLink(){
		return lib::lockUrl(WEB_ROOT . "/user/add/", array('_pageMode'=>'add'));
	}
	
	function getEditLink( $userId, $from ){
		return lib::lockUrl(WEB_ROOT . "/user/{$userId}/", array('_pageMode'=>'edit', 'from' => $from));
	}

	function ax_suspend(){
	
		$status	= self::suspend;
		
		$users	= $this->get( explode(',', args::get('users', 'nothing')));
		$data = array();

		foreach ( $users as $user ){

			$data[] = array( 'id' => $user['id'], 'status' => $status );
		}

		db::multiQuery('user', $data, array('status'));
		
		echo $this->ajaxReturn(true);
	}
	
	function ax_changeRole(){

		$role	= args::get('role', 'nothing');

		if ( !in_array( $role, Role::getType() ) ) $role = _ROLE::DEFAULT_ROLE;

		$users	= $this->get( explode(',', args::get('users', 'nothing')));

		$data = array();

		foreach ( $users as $user ){

			if ( $role == $user['role'] ) continue;

			$data[] = array( 'id' => $user['id'], 'role' => $role );
		}

		db::multiQuery('user', $data, array('role'));

		echo $this->ajaxReturn(true);
	}

	function ax_verify(){

		$status = args::get('status', 'white_list', array(self::notAuth, self::suspend, self::verified));
		$users	= $this->get( explode(',', args::get('users', 'nothing')) );

		$data = array();

		foreach ( $users as $user ){

			if ( $status == $user['status'] ) continue;

			$tmp = array( 'id' => $user['id'], 'status' => $status );

			$tmp['role'] = $user['role'];
			$tmp['verifiedBy'] = profile::$id;

			if ( !$user['role'] ) $tmp['role'] = _ROLE::DEFAULT_ROLE;

			$data[] = $tmp;

			if ( $status == self::verified ) {
				lib::mail($user['email'],
					_T('user-verified-mail-title'),
					_T('user-verified-mail-content', array(
						'%siteName%'=> sys::getCfg('siteName'),
						'%name%'	=> lib::htmlChars( $user['name'] ),
						'%link%'	=> url::getSite() . WEB_ROOT
					))
				);
			}
		}

		db::multiQuery('user', $data, array('status', 'role', 'verifiedBy'));

		prog::get('mgr')->mgrItemClac();

		echo $this->ajaxReturn(true);
	}

	function ax_setRole(){

		$role = args::get('role', 'white_list', array(false, true));
		$users	= explode(',', args::get('users', 'nothing'));

		echo $this->ajaxReturn(true);
	}

    function ax_logout() {
        log::save('user', 'logout', profile::$id);
		profile::reset();
		echo $this->ajaxReturn(true);
    }

}