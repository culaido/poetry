<?php

Class folder Extends Page {

	public function editable(){
		return Priv::check('folder', 'editable');
	}

    function exec($param, $action) {

		if ( $action == 'add' ) {

            $parentID	= args::get('parentID', 'int');
            $auth		= args::get('auth',		'nothing');

            if ($auth != lib::makeAuth( array( $action, $parentID ) ) ) return;

            $data = array('parentID'  => $parentID);

            $folder = $this->create($data);
            $folder = $folder['data'];

            $this->_id		= $folder['id'];
            $editMode_url	= $this->getEditLink($this->_id);

            url::redir($editMode_url);
            exit();
		}

		$this->_id = $param[0];
		if ( self::$_pageMode == 'edit' ) {
			require("action/edit.inc");  // include file to prepare page data
		} else {

			if ( $action == '' ) $action = 'base';
			$action_file = PAGE_PATH . "/folder/action/{$action}.inc";

			if (file_exists($action_file))
				require($action_file);
			else
				require("action/all.inc");  // include file to prepare page data

		}
	}

	function formSave(){

		if (self::$_pageMode == 'edit') {

			$data = array(
				'no'	=> $this->getArgs('no',  	'int'),
				'title' => $this->getArgs('title',  'string')
			);

			$this->save($this->getArgs('id', 'int'), $data);

			CForm::onSuccess('redir', 'parentReload');

			return;
		}
	}

	public function create($param){

		db::query('INSERT INTO folder SET creator=:creator, updater=:updater, uploadComplete=:uploadComplete', array(
				':creator'	=> profile::$id,
				':updater'	=> profile::$id,
				':uploadComplete'	=> _UPLOAD_COMPLETE::notComplete
			));
		$id = db::lastInsertId();

		$param['id'] = $id;

	//	$this->save( $id, array('no' => $id) );
		
		return array('status' => TRUE, 'data' => $param);
	}

	public function save($id, $param){

		$data = array();

		if ($param['password']) $param['password'] = md5( $param['password'] );

		$param['uploadComplete'] = _UPLOAD_COMPLETE::complete;

        foreach ($param as $idx=>$val){

       //     if (in_array($idx, array('id', 'account'))) continue;

            $data['qry'][]   = "{$idx}=:{$idx}";
            $data['param'][":{$idx}"] =  $val;
        }

        $data['param'][':id'] = $id;

        db::query("UPDATE folder SET " . join(', ', $data['qry']) . ", lastUpdate=NOW()  WHERE id=:id", $data['param']);

        return array('status' => TRUE);
	}

	private $_getInfo;
	public function get($id, $type = 'id')	{
        if (!is_array($id)) $id = array($id);

        if (count($id)==1 && $this->_getInfo[$type][$id[0]]){
            return array($id[0] => $this->_getInfo[$type][$id[0]]);
        }

		$folder = db::select("SELECT * FROM folder WHERE FIND_IN_SET({$type}, ?)", array(join(',', $id)));

        $ret = array();
		while ($obj = $folder->fetch()) {
			$this->_getInfo[$type][$obj['id']] = $obj;
            $ret[ $obj['id'] ] = $this->_getInfo[$type][$obj['id']];
		}

		return $ret;
	}

	public function vw_edit(){
		echo theme::block('', join('', $this->form));
	}

	public function vw_title() {

		$addLink = ( $this->editable() )
			? theme::a( _T('poetry-add'), prog::get('poetry')->getAddLink(url::get(), $this->doc['id']) )
			: '';

		echo theme::header( lib::htmlChars(
			$this->doc['title']) ,
			'<div class="pull-right">' . $addLink . '</div>'
		);
	}

	public function vw_list() {
		$this->includeClass('list.php');

		$list = new CList('t1');

		$hdr = array(
			'no'		=> array('title' => _T('no'),				'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'title'		=> array('title' => _T('folder-title'),		'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'poetryCnt'	=> array('title' => _T('folder-poetryCnt'),	'width' => '50px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'=> array('title' => _T('update'),			'width' => '80px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'allow'			=> array('no', 'title', 'poetryCnt', 'lastUpdate'),
				'order' 		=> $this->order,
				'precedence'	=> $this->precedence,
				'url'			=> $this->getlink( 'all' )
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v) $align[$k] = $v['cAlign'];

		$list->setDataAlign($align);

		$url = array();

		foreach ( $this->folders as $v ){

			$list->setData(array(
				'no'			=> ( $v['no'] ) ? $v['no'] : '',
				'title'			=> theme::a( lib::htmlChars($v['title']), $this->getlink( $v['id'] )),
				'poetryCnt'		=> ( $v['poetryCnt'] == 0 ) ? '-' : number_format( $v['poetryCnt'] ),
				'lastUpdate'	=> lib::dateFormat( $v['lastUpdate'], 'm-d')
			));
		}

		echo theme::block( '', $list->Close() );
	}

	function ax_changeRole(){

		$role	= args::get('role', 'white_list', array(_ROLE::ADMIN, _ROLE::CHIEF, _ROLE::MEMBER));
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
			if ( !$user['role'] ) $tmp['role'] = _ROLE::MEMBER;

			$data[] = $tmp;

			if ( $status == self::verified ) {
				lib::mail($user['email'],
					_T('user-verified-mail-title'),
					_T('user-verified-mail-content', array(
						'%name%' => lib::htmlChars( $user['name'] ),
						'%link%' => url::getSite()
					))
				);
			}
		}

		db::multiQuery('user', $data, array('status', 'role'));
		echo $this->ajaxReturn(true);
	}

	function remove( $id ){

		$folder = array_shift( $this->get( $id ) );

		if ( !$folder ) {
			return array('status' => false, 'msg' => _T('folder-notExist'));
		}

		event::trigger('folder.before.remove', array('id' => $id));

		db::query('DELETE FROM folder WHERE id=:id', array(':id' => $id));
		log::save('folder', "remove {$folder['title']}", profile::$id);

		return array('status' => true);
	}

    function ax_remove() {

		$id  = args::get('id', 'int');
		$ret = $this->remove( $id );

		echo $this->ajaxReturn($ret['status'], $ret['msg']);
    }

    function ax_removeList() {

		$ids  = explode(',', args::get('id', 'nothing'));

		foreach ( $ids as $id ){

			$ret  = $this->remove( $id );
			if ( $ret['status'] == false) {
				echo $this->ajaxReturn($ret['status'], $ret['msg']);
				exit;
			}
		}

		echo $this->ajaxReturn(true);
    }

	public function getlink( $id ) {
		return WEB_ROOT . '/folder/' . $id;
	}

	public function getList( $args=array() ){

		if ( $args['order']			== '' ) $args['order']		= 'id';
		if ( $args['precedence']	== '' ) $args['precedence']	= 'ASC';
		if ( $args['limit']			!= '' ) $args['limit']		= 'LIMIT ' . $args['limit'];

		$folders = db::select("SELECT * FROM folder WHERE uploadComplete=:uploadComplete ORDER BY {$args['order']} {$args['precedence']} {$args['limit']}",
			array(':uploadComplete' => _UPLOAD_COMPLETE::complete)
		);

		$folder = array();

		while ($obj = $folders->fetch()){
			$folder[ $obj['id'] ] = $obj;
		}

		return $folder;
	}

	public function getAddLink( $parentID ) {
		return WEB_ROOT . "/folder/add/?from=mgr&parentID={$parentID}&auth=" . lib::makeAuth( array( 'add', $parentID ) );
	}

	public function getEditLink( $id ) {
		return lib::lockUrl(WEB_ROOT . "/folder/{$id}/", array('_pageMode'=>'edit'));
	}

	public function getRemoveLink( $id ) {
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.folder/folder.remove", array('id'=> $id));
	}

	public function getRemoveListLink() {
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.folder/folder.removeList", array('profile'=> profile::$id));
	}

	public function poetryCount($folderIds) {
		$folders = (!is_array($folderIds)) ? array($folderIds) : $folderIds;
		foreach ($folders as $val) {
			db::query('UPDATE folder SET poetryCnt=(SELECT COUNT(*) FROM poetry WHERE folderID=:folderID AND uploadComplete=1) WHERE id=:folderID', array(':folderID' => $val));
		}
	}

	public function getCnt(){
		$total = db::fetch('SELECT COUNT(*) AS cnt FROM folder WHERE uploadComplete=:uploadComplete', array(
			':uploadComplete' => _UPLOAD_COMPLETE::complete
		));

		return array('total' => $total['cnt']);
	}

	function hk_onPoetrySave( $param ){

		ignore_user_abort(TRUE);
		$id    = $param['id'];

		$poetry = array_shift( prog::get('poetry')->get($id) );
		$this->poetryCount( $poetry['folderID'] );

	}

	function hk_onPoetryRemove( $param ){

		ignore_user_abort(TRUE);
		$this->poetryCount( $param['folderID'] );

	}


}