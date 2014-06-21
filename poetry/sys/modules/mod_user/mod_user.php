<?php

Class mod_user Extends Module {

	const suspend	= 0;
	const notAuth	= 1;
	const verified	= 2;

	public function editable() {
		return Priv::check('user', 'editable');
	}

	public function ac_mgr(){
		$this->includeClass('sqllist.php');

		$this->order		= args::get('order', 'white_list', array('lastLoginTime', 'status', 'role', 'account', 'name', 'loginTimes', 'id'));
		$this->precedence	= args::get('precedence', 'white_list', array('DESC', 'ASC'));

		$sql  = "SELECT * FROM user WHERE status!=? ORDER BY {$this->order} {$this->precedence}";

		$this->user = new SQLList(array(
			'query'		=> array(
				'sql'   => $sql,
				'param' => array(
					self::suspend
				)
			),
			'page' => args::get('page', 'int', 1),
			'size' => args::get('size', 'int', 10)
		));
	}

	public function vw_mgr(){

		$this->includeClass('list.php');

		$list = new CList('t1', 'checkbox');

		$hdr = array(
			'ck'			=> array('title' => '',					'width' => '40px',  'align' => 'center',	'cAlign' =>	'center'),
			'status'		=> array('title' => _T('status'),		'width' => '50px',  'align' => 'center',	'cAlign' =>	'center'),
			'role'			=> array('title' => _T('role'),			'width' => '50px',	'align' => 'center',	'cAlign' =>	'center'),
			'name'			=> array('title' => _T('name'),			'width' => '130px',	'align' => 'left',		'cAlign' =>	'left'),
			'email'			=> array('title' => _T('email'),		'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'phone'			=> array('title' => _T('phone'),		'width' => '100px',	'align' => 'left',		'cAlign' =>	'left'),
			'lastLoginTime'	=> array('title' => _T('login'),		'width' => '50px',	'align' => 'center',	'cAlign' =>	'center'),
			'loginTimes'	=> array('title' => _T('loginTimes'),	'width' => '50px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'order'			=> $this->order,
				'allow'			=> array('id', 'status', 'role', 'name', 'lastLoginTime', 'loginTimes'),
				'precedence'	=> $this->precedence,
				'url'			=> WEB_ROOT . '/mgr/userMgr/'
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v){
			$align[$k] = $v['cAlign'];
		}

		$list->setDataAlign($align);

		$statusIco = array(
			self::suspend	=> theme::icon('times',		array('style' => 'color:#f00', 'title' => _T('userSuspend'))),
			self::notAuth	=> theme::icon('question',	array('style' => 'color:#f00', 'title' => _T('userNotAuth'))),
			self::verified	=> '-'
		);

		$role = Role::get();

		$roleIco = array();
		foreach ( $role as $v ) {
			$roleInfo[] = "<li>" . theme::icon( $v['class'] )  . ' ' . $v['name'] . ': ' . lib::htmlChars($v['info']) . "</li>";
			if ( $v['id'] != _ROLE::SYSOP) $roleChg[]  = "<li><a href='javascript:changeRole({$v['id']})'>" . theme::icon( $v['class'] ) . " " . lib::htmlChars($v['name']) . "</a></li>";

			$roleIco[ $v['id'] ] = theme::icon( $v['class'],  array('title' => lib::htmlChars($v['name'])) );
		}

		while ($v = $this->user->fetch()) {

			$lastLoginTime = ($v['lastLoginTime'])
				? "<span title='{$v['lastLoginTime']}'>" . lib::dateFormat( $v['lastLoginTime'], 'm-d' ) . "</span>"
				: '-';

			$list->setData(array(
				'ck'		=> $v['id'],
				'status'	=> $statusIco[ $v['status'] ],
				'role'		=> $roleIco[ $v['role'] ],
				'account'	=> lib::htmlChars($v['account']),
				'name'		=> '<span title="' . lib::htmlChars($v['account']) . '">' . lib::htmlChars($v['name']) . ' <span class="hint"> - ' . lib::htmlChars($v['account']) . '</span></span>',
				'email'		=> '<span class="hint">' . theme::a(lib::htmlChars($v['email']), "mailto:".lib::htmlChars($v['email']), '_top') . '</span>',
				'phone'		=> '<span class="hint" title="' . lib::htmlChars($v['phone']) . '">' . lib::htmlChars($v['phone']) . '</span>',
				'lastLoginTime'	=> $lastLoginTime,
				'loginTimes'	=> number_format($v['loginTimes'])

			), ( $v['role'] == _ROLE::SYSOP ) ? 'disabled' : '');
		}


		$args = array(
			"order=" . urlencode($this->order),
			"precedence=" . $this->precedence
		);


		if ( $this->editable() )
			$panel = "
				<div class='pull-right'>
					<button type='button' class='btn btn-primary' role='user-add'>" . _T('user-add') . "</button>

					<div class='btn-group' style='margin-left:10px'>
						<button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>"
							. _T('verify member') .
							" <span class='caret'></span>
						</button>
						<ul class='dropdown-menu' role='menu-auth'>
							<li><a href='javascript:verifyUser(true)'>"	 . _T('ok')		. "</a></li>
							<li><a href='javascript:verifyUser(false)'>" . _T('cancel')	. "</a></li>
						</ul>
					</div>

					<div style='margin-left:10px' class='btn-group'>

						<button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>"
							. _T('change role') .
							" <span class='caret'></span>
						</button>
						<ul class='dropdown-menu' role='menu-role'>
							" . join('', $roleChg) . "
						</ul>
					</div>
					
					<button style='margin-left:10px' type='button' class='btn btn-danger' role='user-suspend'>" . _T('remove') . "</button>

				</div>";


		echo theme::block(_T('Member Mgr Title'),
			$list->Close() .
			"<div class='clearfix'>
				{$panel}
				" . theme::page($this->user->currPage, $this->user->pageNum, WEB_ROOT . '/mgr/userMgr/?' . join('&', $args), 10) . "
			</div>
			<div><ul style='padding:0; list-style:none; line-height:1.8'>" . join('', $roleInfo) . "</ul></div>"
		);

		$url = json_encode( array(
			'verifyOK'		=> lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.user/user.verify",		array('status'=>self::verified)),
			'verifyCancel'	=> lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.user/user.verify",		array('status'=>self::notAuth)),

			'changeRole'	=> lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.user/user.changeRole",	array('id'=>profile::$id)),
			
			'add'		=> prog::get('user')->getAddLink(),
			'suspend'	=> lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.user/user.suspend",	array('id'=>profile::$id))
		) );

		$lang = json_encode( array(
			'userAdd'		=> _T('user-add'),
			'settingOver' 	=> _T('settingOver'),
			'removeCf' 		=> _T('removeCf'),
			'selectNoData'	=> _T('selectNoData')
		) );

		$js = <<<JS
			var url	 = {$url}
				lang = {$lang};

			function changeRole( role ){

				var users = _lstGetItem('t1');
				if ( users == '' ) {
					alert(lang.selectNoData);
					return;
				}

				$.post(url['changeRole'], {role:role, users:users},
					function(obj){

						if ( obj.ret.status == false ){
							alert( obj.ret.msg );
							return;
						}

						alert(lang.settingOver);
						window.location.reload();

					}, 'json');

			}

			function verifyUser(status){

				var users = _lstGetItem('t1');
				if ( users == '' ) {
					alert(lang.selectNoData);
					return;
				}

				$.post((status) ? url['verifyOK'] : url['verifyCancel'], {users:users},
					function(obj){

						if ( obj.ret.status == false ){
							alert( obj.ret.msg );
							return;
						}

						alert(lang.settingOver);
						window.location.reload();

					}, 'json');
			}
			
			$("[role='user-suspend']").click(function(){
				
				var users = _lstGetItem('t1');
				if ( users == '' ) {
					alert(lang.selectNoData);
					return;
				}
				
				if ( !confirm(lang.removeCf) ) return;
				
				$.post(url.suspend, {users:users},
					function(obj){

						if ( obj.ret.status == false ){
							alert( obj.ret.msg );
							return;
						}

						alert(lang.settingOver);
						window.location.reload();

					}, 'json');
			});
			
			$("[role='user-add']").click(function(){
				ui.modal.show(lang.userAdd, url.add, 600, 500);
			});

JS;
		js::add($js);
	}

	public function mgrTitle($link){

		if ( !$this->editable() ) return false;

		$notAuth	= db::fetch('SELECT COUNT(*) AS cnt FROM user WHERE status=?', array(self::notAuth));

		if ( $notAuth['cnt'] )
			$badge = " <span title='" . _T('None Auth Member count') . "'>" . theme::badges($notAuth['cnt'], 'info') . "</span>";

		return theme::a( _T('Member Mgr Title') . $badge, $link);

	}

}