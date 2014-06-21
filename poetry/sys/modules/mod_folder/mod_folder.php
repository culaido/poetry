<?php
Class mod_folder Extends Module {

	public function ac_mgr(){

		$this->order		= args::get('order', 'white_list', array('no', 'title', 'poetryCnt', 'lastUpdate'));
		$this->precedence	= args::get('precedence', 'white_list', array('DESC', 'ASC'));

		$this->folders = prog::get('folder')->getList( array(
			'order'			=> $this->order,
			'precedence'	=> $this->precedence
		) );
	}

	public function vw_mgr(){

		$this->includeClass('list.php');

		$list = new CList('t1', 'checkbox');

		$hdr = array(
			'ck'			=> array('title' => '',						'width' => '40px',  'align' => 'center',	'cAlign' =>	'center'),
			'no'			=> array('title' => _T('no'),				'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'title'			=> array('title' => _T('folder-title'),		'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'poetryCnt'		=> array('title' => _T('folder-poetryCnt'),	'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'	=> array('title' => _T('update'),			'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'tool'			=> array('title' => _T('tool'),				'width' => '70px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'order'			=> $this->order,
				'allow'			=> array('no', 'title', 'poetryCnt', 'lastUpdate'),
				'precedence'	=> $this->precedence,
				'url'			=> WEB_ROOT . '/mgr/folderMgr/'
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v){
			$align[$k] = $v['cAlign'];
		}

		$list->setDataAlign($align);

		$url = array();

		$folderObj = prog::get('folder');

		$editable = $this->editable();
		
		if ( is_array($this->folders) ){

			foreach ($this->folders as $v){

				if ( $editable ) {

					$tool = theme::a(theme::icon('wrench'), "javascript:edit({$v['id']})",	 	'', array( 'style'=>'color:#000', 'title' => _T('edit') ))
								. '<span style="margin:0 5px"></span>' .
							theme::a(theme::icon('times'),  "javascript:remove({$v['id']})",	'', array( 'style'=>'color:#f00', 'title' => _T('remove') ));
						
			
					$url['edit'][ $v['id'] ]	= $folderObj->getEditLink($v['id']);
					$url['remove'][ $v['id'] ]	= $folderObj->getRemoveLink($v['id']);
				}
				$list->setData(array(
					'ck'		=> $v['id'],
					'no'		=> ( !$v['no'] ) ? '' : $v['no'],
					'title'		=> theme::a( lib::htmlChars($v['title']), WEB_ROOT . "/folder/{$v['id']}"),
					'poetryCnt'	=> number_format( $v['poetryCnt'] ),
					'lastUpdate'=> date('m-d', $v['lastUpdate']),
					'tool'		=> $tool
				));

			}
		}

		$btn = ( $editable ) 
			? "<div class='clearfix text-right' style='margin-bottom:10px'>
				<button type='button' role='add' class='btn btn-primary'>" . theme::icon('plus') . " " . _T('add') . "</button>
					&nbsp;
				<button type='button' role='remove' class='btn btn-default'>" . theme::icon('times') . " " . _T('remove') . "</button>
			</div>" : '';
		
		echo theme::block(_T('Folder Mgr'), $btn . $list->Close() . $btn);

		if ( !$editable ) return;
		
		$parentID			= args::get('parentID', 'int');
		$url['add']			= $folderObj->getAddLink($parentID);
		$url['removeList']	= $folderObj->getRemoveListLink();

		$url = json_encode($url);
		$lang = json_encode( array(
			'folderAdd'	 => _T('folder-add'),
			'folderEdit' => _T('folder-edit'),
			'folderRemoveCf' => _T('folder-removeCf'),
		) );

		$js = <<<JS

			var url  = {$url},
				lang = {$lang};

			$(function(){
				$('[role="add"]').click(function(){
					ui.modal.show(lang.folderAdd, url.add, 600, 250);
				});

				$('[role="remove"]').click(function(){

					if ( !confirm(lang.folderRemoveCf) ) return;

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
				ui.modal.show(lang.folderEdit, url.edit[id], 600, 250);
			}

			function remove( id ){
				if ( !confirm(lang.folderRemoveCf) ) return;
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


	public function ac_list(){
		$this->folders = prog::get('folder')->getList( array(
			'order' 	 => 'poetryCnt',
			'precedence' => 'DESC',
			'limit'		 => 10
		) );
	}

	public function vw_list( $param ){
		$focus	= $param['args']['focus'];

		$list	= array();

		$cls['focus'][$focus] = 'focus';
/*
		$list[] = array (
			'prop' => array('class' => $cls['focus'][ 0 ]),
			'html' => "<div class='title'>" . theme::a( lib::htmlChars( _T('folder-root') ), WEB_ROOT . '/folder/' . 0, '', array('title' => lib::htmlChars( _T('folder-root') ) ) ) . "</div>"
		);
*/
		foreach ($this->folders as $v){

			$list[] = array(
				'prop' => array('class' => $cls['focus'][ $v['id'] ] ),
				'html' => "
					<div class='pull-right hint'>" . number_format( $v['poetryCnt'] ) . ' ' . _T('poetry') . "</div>
					<div class='title'>" . theme::a( lib::htmlChars($v['title']), WEB_ROOT . '/folder/' . $v['id'], '',
											array('title' => lib::htmlChars($v['title']) . " ({$v['poetryCnt']})")) . "</div>"
			);
		}

		echo '<div class="folderList">' . theme::block(
				_T('folder') . '<span class="hint pull-right">' . theme::a('more', $this->getLink('all') ) . '</span>',
				theme::lists($list)
			) . '</div>';
	}

	public function getlink( $id ) {
		return prog::get('folder')->getlink( $id );
	}

	public function mgrTitle( $link ){
		if ( !$this->editable() ) return false;

		return theme::a( _T('Folder Mgr'), $link);
	}
	
	public function editable(){
		return prog::get('folder')->editable();
	}

}