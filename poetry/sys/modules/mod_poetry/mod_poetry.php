<?php
Class mod_poetry Extends Module {

	public function mgrTitle($link){

		if ( !$this->editable() ) return false;

		$url = $this->getAddLink(WEB_ROOT . '/mgr/poetryMgr/', 0);
		js::add(<<<JS

			function poetryAdd(){
				window.location.href = '{$url}';
			}
JS
);
		return	theme::a( _T('Poetry Mgr'), $link) .
				theme::a( _T('poetry-add'), $this->getAddLink($link, 0));

	}

	public function ac_mgr(){
		$this->folders = prog::get('folder')->getList();

		$this->includeClass('sqllist.php');

		$this->order		= args::get('order', 'white_list', array('no', 'title', 'folderID', 'lastUpdate'));
		$this->precedence	= args::get('precedence', 'white_list', array('DESC', 'ASC'));
		$this->page			= args::get('page', 'int', 1);

		$this->poetrys = new SQLList(array(
			'query'		=> array(
				'sql'   => "SELECT * FROM poetry WHERE uploadComplete=:uploadComplete ORDER BY {$this->order} {$this->precedence}",
				'param'	=> array(
					':uploadComplete' => _UPLOAD_COMPLETE::complete
				)
			),
			'page' => $this->page,
			'size' => args::get('size', 'int', 30)
		));

		$this->folders[0]['title'] = _T('folder-root');
	}

	public function vw_mgr(){

		$this->includeClass('list.php');

		$list = new CList('t1', 'checkbox');

		$hdr = array(
			'ck'			=> array('title' => '',					'width' => '40px',  'align' => 'center',	'cAlign' =>	'center'),
			'no'			=> array('title' => _T('poetry-no'),	'width' => '60px',  'align' => 'center',	'cAlign' =>	'center'),
			'title'			=> array('title' => _T('folder-title'),	'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'folderID'		=> array('title' => _T('folder'),		'width' => '120px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'	=> array('title' => _T('update'),		'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'tool'			=> array('title' => _T('tool'),			'width' => '70px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'order'			=> $this->order,
				'allow'			=> array('no', 'title', 'folderID', 'lastUpdate'),
				'precedence'	=> $this->precedence,
				'url'			=> WEB_ROOT . '/mgr/poetryMgr/'
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v){
			$align[$k] = $v['cAlign'];
		}

		$list->setDataAlign($align);

		$url = array();

		$poetryObj = prog::get('poetry');
		$editable = $this->editable();

		while ($v = $this->poetrys->fetch()){

			if ( $editable ) {

				$url['edit'][ $v['id'] ]	= $poetryObj->getEditLink($v['id'], url::get());
				$url['remove'][ $v['id'] ]	= $poetryObj->getRemoveLink($v['id']);

				$tool =
					theme::a(theme::icon('wrench'), "javascript:edit({$v['id']})",	 	'', array( 'style'=>'color:#000', 'title' => _T('edit') ))
						. '<span style="margin:0 5px"></span>' .
					theme::a(theme::icon('times'),  "javascript:remove({$v['id']})",	'', array( 'style'=>'color:#f00', 'title' => _T('remove') ));
			}

			$list->setData(array(
				'ck'		=> $v['id'],
				'no'		=> $v['no'],
				'title'		=> theme::a( lib::htmlChars($v['title']), $poetryObj->getLink( $v['id'] ) ),
				'folderID'	=> theme::a( lib::htmlChars( $this->folders[ $v['folderID'] ]['title'] ), WEB_ROOT . '/folder/' . $v['folderID']),
				'lastUpdate'=> lib::dateFormat( $v['lastUpdate'], 'm-d'),
				'tool'		=> $tool

			));
		}

		$args = array(
			"order=" . urlencode($this->order),
			"precedence=" . $this->precedence
		);

		$btn = ( $editable )
			? "<div class='clearfix text-right' style='margin-bottom:10px'>
				<button type='button' role='add' class='btn btn-primary'>" . theme::icon('plus') . " " . _T('add') . "</button>
					&nbsp;
				<button type='button' role='remove' class='btn btn-default'>" . theme::icon('times') . " " . _T('remove') . "</button>
			</div>" : '';

		echo theme::block(_T('Poetry Mgr'),
			$btn . $list->Close() . theme::page($this->poetrys->currPage, $this->poetrys->pageNum, WEB_ROOT . '/mgr/poetryMgr/?' . join('&', $args), 10) .
			$btn
		);

		if ( !$editable ) return;

		$parentID			= args::get('parentID', 'int');
		$url['add']			= $this->getAddLink(url::get(), $parentID);
		$url['removeList']	= $poetryObj->getRemoveListLink();

		$url = json_encode($url);

		$lang = json_encode( array(
			'poetryAdd'		=> _T('poetry-add'),
			'poetryEdit'	=> _T('poetry-edit'),
			'removeCf'		=> _T('removeCf')
		) );

		$js = <<<JS

			var url  = {$url},
				lang = {$lang};

			$(function(){
				$('[role="add"]').click(function(){
					window.location.href = url.add;
				});

				$('[role="remove"]').click(function(){

					if ( !confirm(lang.removeCf) ) return;

					$.post(url.removeList, {id:_lstGetItem('t1')}, function(obj){
						if ( obj.ret.status == false){
							alert( obj.ret.msg );
							return;
						}

						window.location.reload();
					}, 'json');

				});
			});

			function edit( id ){
				window.location.href = url.edit[id];
			}

			function remove( id ){
				if ( !confirm(lang.removeCf) ) return;
				$.post(url.remove[id], {}, function(obj){
					if ( obj.ret.status == false){
						alert( obj.ret.msg );
						return;
					}

					window.location.reload();
				}, 'json');
			}

JS;
		js::add($js);
	}

	public function ac_list($param){

		$this->includeClass('sqllist.php');

		$this->folderID = $param['args']['folderID'];

		$this->order		= args::get('order', 'white_list', array('no', 'title', 'view', 'lastUpdate'));
		$this->precedence	= args::get('precedence', 'white_list', array('DESC', 'ASC'));

		$sql  = "SELECT * FROM poetry WHERE
					uploadComplete=:uploadComplete AND
					folderID=:folderID
					ORDER BY {$this->order} {$this->precedence}";

		$this->poetrys = new SQLList(array(
			'query'		=> array(
				'sql'   => $sql,
				'param' => array(
					':uploadComplete'	=> _UPLOAD_COMPLETE::complete,
					':folderID'			=> $this->folderID
				)
			),
			'page' => args::get('page', 'int', 1),
			'size' => args::get('size', 'int', 20)
		));
	}

	public function vw_list(){

		$this->includeClass('list.php');
		$list = new CList('t1');

		$hdr = array(
			'no'			=> array('title' => _T('poetry-no'),	'width' => '60px',  'align' => 'center',	'cAlign' =>	'center'),
			'title'			=> array('title' => _T('poetry-title'),	'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'view'			=> array('title' => _T('poetry-view'),	'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'	=> array('title' => _T('update'),		'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'media'			=> array('title' => _T('media'),			'width' => '60px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'order'			=> $this->order,
				'allow'			=> array('no', 'title', 'view', 'lastUpdate'),
				'precedence'	=> $this->precedence,
				'url'			=> WEB_ROOT . "/folder/{$this->folderID}"
			)
		));
		$align = array();
		foreach ($hdr as $k=>$v){
			$align[$k] = $v['cAlign'];
		}

		$list->setDataAlign($align);

		$url = array();

		$embedData = array();

		while ( $v = $this->poetrys->fetch()){

			$embedCode = '';
			if ( $v['embed'] ) {
				$embedData[ $v['id'] ] = array( 'title' => $v['title'], 'embed' => $v['embed'] );
				$embedCode = "<span embedId='{$v['id']}' role='media' height='{$v['height']}' width='{$v['width']}' style='cursor:pointer'>" . theme::icon('youtube-play') . "</span>";
			}

			$list->setData(array(
				'no'		=> $v['no'],
				'title'		=> theme::a( lib::htmlChars($v['title']), WEB_ROOT . '/poetry/' . $v['id']),
				'view'		=> $v['view'],
				'lastUpdate'=> lib::dateFormat( $v['lastUpdate'], 'm-d'),
				'media'		=> $embedCode
			));
		}

		$args = array(
			"order="		. urlencode($this->order),
			"precedence="	. $this->precedence
		);

		$embedData = json_encode( $embedData );

		$js = <<<js
			$(function(){
				var data = {$embedData};
				$('[role="media"]').click(function(){

					var embed = $('<div>', {'class' : 'embed'});

					var d = data[ $(this).attr('embedId') ];
					embed.append( d.embed )

					ui.modal.html( d.title, embed, 600, 380 );

				});
			});
			
	
			$.Evt('modal.show').subscribe(function(){
				$('.embed').width( $('.modal-body').width() );
				$('.embed').height( $('.modal-body').width() * 360 / 600 );
			});
js;

		js::add($js);

		echo theme::block('',
			$list->Close() .
			theme::page($this->poetrys->currPage, $this->poetrys->pageNum, WEB_ROOT . "/folder/{$this->folderID}/?" . join('&', $args), 10)
		);

	}

	public function ac_newest($param){

		$limit = ($param['args']['limit']) ? $param['args']['limit'] : 5;
		$this->newest = db::select("SELECT * FROM poetry WHERE uploadComplete=:uploadComplete ORDER BY createTime DESC LIMIT {$limit}", array(
			':uploadComplete' => _UPLOAD_COMPLETE::complete
		));
	}

	public function vw_newest(){

		$list = array();

		foreach ($this->newest as $v){
			$list[] = "
				<div class='pull-right hint'>" . lib::timespan( $v['createTime'] ) . "</div>
				<div class='title'>" . theme::a( lib::htmlChars($v['title']), WEB_ROOT . "/poetry/{$v['id']}") . "</div>";
		}

		echo '<div class="newestList">' . theme::block(_T('poetry-newest'), theme::lists($list)) . '</div>';
	}

	public function ac_hotest($param){

		$limit = ($param['args']['limit']) ? $param['args']['limit'] : 5;
		$this->hotest = db::select("SELECT * FROM poetry WHERE uploadComplete=:uploadComplete ORDER BY view DESC LIMIT {$limit}", array(
			':uploadComplete' => _UPLOAD_COMPLETE::complete
		));
	}

	public function vw_hotest(){

		$list = array();

		foreach ($this->hotest as $v){

			$list[] = "
				<div class='pull-right hint'>" . number_format( $v['view'] ) . ' ' .  _T('poetry-view') . "</div>
				<div class='title'>" . theme::a( lib::htmlChars($v['title']), WEB_ROOT . "/poetry/{$v['id']}") . "</div>";
		}

		echo '<div class="hotestList">' . theme::block(_T('poetry-hotest'), theme::lists($list)) . '</div>';
	}

	public function getAddLink( $from, $folderID ) {
		return prog::get('poetry')->getAddLink( $from, $folderID );
	}


	public function editable(){
		return prog::get('poetry')->editable();
	}
}