<?php
Class playlist extends Page {

	private $doc;
	
	private $open	= 0;
	private $close	= 1;
	
    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

	function priv($param, $action) {
		return $action;
	}

	function formValid(){
		$ret = array();

		if (self::$_pageMode == 'addItem') {

			if ( $this->getArgs('createNew', 'int') == 1 ) {

				$title = $this->getArgs('title', 'string');

				if ( !$title ){
					$ret[] = $this->formReturn('title', _T('playlist-titleEmptyPS'));
				}
			}
		}


		return $ret;
	}

	function formSave(){

		if (self::$_pageMode == 'edit') {

			$id = $this->getArgs('id', 'int');

			$this->save($id, array(
				'title' => $this->getArgs('title', 'string'),
				'priv'	=> $this->getArgs('priv',  'int')
			));

			CForm::onSuccess('redir', 'parentReload');
		}

		if (self::$_pageMode == 'addItem') {

			if ( $this->getArgs('createNew', 'int') == 1 ) {

				$param = array(
					'title' => $this->getArgs('title', 'string'),
					'priv'	=> $this->getArgs('priv',  'int')
				);

				$playlist = $this->create( $param );
				$playlist = $playlist['data'];

			} else {

				$playlist = array_shift( $this->get( $this->getArgs('playlistId', 'int') ) );
			}

			$param = array(
				'parentID'	=> $playlist['id'],
				'pageId'	=> $this->getArgs('pageId', 'nothing'),
				'type'		=> $this->getArgs('type',	'int')
			);

			$this->createItem( $param );

			$this->save($playlist['id'], array());

			CForm::onSuccess('alert', _T('playlist-inserted',
					array('%title%' => lib::htmlChars($playlist['title']))
				)
			);

			CForm::onSuccess('redir', 'closeModal');
		}
	}

	public function create($param){

		if ( !$param['title'] ) return FALSE;

		db::query('INSERT INTO playlist SET title=:title, priv=:priv, creator=:creator, createTime=NOW(), lastUpdate=NOW()', array(
				':title'	=> $param['title'],
				':priv'		=> $param['priv'],
				':creator'	=> profile::$id
		));

		$id = db::lastInsertId();

		$param['id'] = $id;

		return array('status' => TRUE, 'data' => $param);
	}

	public function createItem($param){

		if ( !$param['parentID'] || !$param['pageId'] ) return FALSE;

		$sn = db::fetch('SELECT MAX(sn) AS sn FROM playlist_item WHERE parentID=:parentID', array(
			':parentID' => $param['parentID']
		));

		$param['sn'] = $sn['sn'] + 10;

		db::query('INSERT INTO playlist_item SET type=:type, parentID=:parentID, pageId=:pageId, creator=:creator, sn=:sn, lastUpdate=NOW()', array(
				':parentID'	=> $param['parentID'],
				':pageId'	=> $param['pageId'],
				':type'		=> $param['type'],
				':creator'	=> profile::$id,
				':sn'		=> $param['sn']
		));

		$id = db::lastInsertId();

		$param['id'] = $id;

		db::query('UPDATE playlist AS p SET poetry=(SELECT COUNT(*) FROM playlist_item WHERE parentID=p.id) WHERE id=:id', array(
			':id' => $param['parentID']
		));

		return array('status' => TRUE, 'data' => $param);

	}

	public function save($id, $param){

		$data = array();

        foreach ($param as $idx=>$val){

            if (in_array($idx, array('id', 'creator'))) continue;

            $data['qry'][]   = "{$idx}=:{$idx}";
            $data['param'][":{$idx}"] =  $val;
        }

        $data['param'][':id'] = $id;
		$data['qry'][] = 'lastUpdate=NOW()';

        db::query("UPDATE playlist SET " . join(', ', $data['qry']) . "  WHERE id=:id", $data['param']);

        return array('status' => TRUE);
	}

	public function exec($param, $action){

		$this->_id = $param[0];

        if ( self::$_pageMode == 'edit' ) {
			require("action/edit.inc");  // include file to prepare page data

		} else {

			if ( $action == '' ) $action = 'base';

			$action_file = PAGE_PATH . "/playlist/action/{$action}.inc";


			if (file_exists($action_file))
				require($action_file);
			else
				require("action/all.inc");  // include file to prepare page data		}
		}
	}

	public function get($id, $type = 'id')	{

        if (!is_array($id)) $id = array($id);

        if (count($id)==1 && $this->_getInfo[$type][$id[0]]){
            return array($id[0] => $this->_getInfo[$type][$id[0]]);
        }

		$playlist = db::select("SELECT * FROM playlist WHERE FIND_IN_SET({$type}, ?)", array(join(',', $id)));

        $ret = array();
		while ($obj = $playlist->fetch()) {
			$this->_getInfo[$type][$obj['id']] = $obj;
            $ret[ $obj['id'] ] = $this->_getInfo[$type][$obj['id']];
		}

		return $ret;
	}

	public function getItem($id, $type = 'id')	{

        if (!is_array($id)) $id = array($id);

        if (count($id)==1 && $this->_getInfo[$type][$id[0]]){
            return array($id[0] => $this->_getInfo[$type][$id[0]]);
        }

		$playlist = db::select("SELECT * FROM playlist_item WHERE FIND_IN_SET({$type}, ?)", array(join(',', $id)));

        $ret = array();
		while ($obj = $playlist->fetch()) {
			$this->_getInfo[$type][$obj['id']] = $obj;
            $ret[ $obj['id'] ] = $this->_getInfo[$type][$obj['id']];
		}

		return $ret;
	}

	public function getList( $args=array() ){

		if ( $args['order']			== '' ) $args['order']		= 'id';
		if ( $args['precedence']	== '' ) $args['precedence']	= 'ASC';
		if ( $args['limit']			!= '' ) $args['limit']		= 'LIMIT ' . $args['limit'];

		$playlists = db::select("SELECT * FROM playlist WHERE priv=:priv OR creator=:creator ORDER BY {$args['order']} {$args['precedence']} {$args['limit']}",
			array(
				':priv'		=> $this->open,
				':creator'	=> profile::$id
			)
		);

		$playlist = array();

		while ($obj = $playlists->fetch()){
			$playlist[ $obj['id'] ] = $obj;
		}

		return $playlist;
	}

	public function getAddItemLink( $pageId ){
		return lib::lockUrl(WEB_ROOT . "/playlist/addItem/", array('_pageMode'=>'addItem', 'pageId' => $pageId));
	}

	public function getLink( $id ){
		return WEB_ROOT . "/playlist/{$id}";
	}

	public function getEditLink( $id ) {
		return lib::lockUrl(WEB_ROOT . "/playlist/edit/{$id}/", array('id' => $id, 'creator' => profile::$id, '_pageMode'=>'edit'));
	}

	public function getRemoveLink( $id ) {
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.playlist/playlist.remove", array('id'=> $id));
	}

	function vw_my(){

		$this->includeClass('list.php');

		$list = new CList('t1');

		$hdr = array(
			'id'			=> array('title' => _T('id'),					'width' => '50px',  'align' => 'center',	'cAlign' =>	'center'),
			'title'			=> array('title' => _T('playlist-title'),		'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'poetry'		=> array('title' => _T('playlist-poetryCnt'),	'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'	=> array('title' => _T('lastUpdate'),			'width' => '100px',	'align' => 'center',	'cAlign' =>	'center'),
			'action'		=> array('title' => _T('tool'),					'width' => '60px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr);

		$align = array();
		foreach ($hdr as $k=>$v) $align[$k] = $v['cAlign'];
		$list->setDataAlign($align);

		$url = array();

		foreach ($this->playlist as $k=>$v){

			$url['edit'][ $v['id'] ]	= $this->getEditLink( $v['id'] );
			$url['remove'][ $v['id'] ]	= $this->getRemoveLink($v['id']);

			$title = ( $v['poetry'] == 0 )
				? lib::htmlChars($v['title']) . ' <span class="hint">( ' . _T('playlist-noPeotry') . ' )</span>'
				: theme::a( lib::htmlChars($v['title']), $this->getLink($v['id']) );

			$list->setData( array(
				'id'		=> $v['id'],
				'title'		=> $title,
				'poetry'	=> $v['poetry'],
				'lastUpdate'=> lib::timeSpan( $v['lastUpdate'] ),
				'action'	=>
						theme::a(theme::icon('wrench'), "javascript:edit({$v['id']})",	 	'', array( 'style'=>'color:#000', 'title' => _T('edit') ))
							. '<span style="margin:0 5px"></span>' .
						theme::a(theme::icon('times'),  "javascript:remove({$v['id']})",	'', array( 'style'=>'color:#f00', 'title' => _T('remove') ))

			));
		}

		$url = json_encode($url);
		$lang = json_encode( array(
			'playlistRemoveCf'	=> _T('playlist-removeCf'),
			'edit' 				=> _T('edit')
		) );

		$js = <<<JS

			var url  = {$url},
				lang = {$lang};

			function edit( id ){
				ui.modal.show(lang.edit, url.edit[id], 600, 400);
			}

			function remove( id ){
				if ( !confirm(lang.playlistRemoveCf) ) return;

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


		echo theme::block( _T('playlist-my'), $list->Close() );
	}

	function vw_edit(){
		echo theme::block('', join('', $this->form) );
	}

	function vw_sort(){

		if ( count( $this->items ) == 0 ) return;

		$list = $url = array();
		$sn = 1;
		foreach ( $this->items as $v ) {

			list($page, $id) = lib::parsePageId( $v['pageId'] );

			$url[ $v['id'] ]['remove'] = $this->getRemoveItemLink( $v['id'] );

			$title = $this->poetry[$id]['title'];

			if ( !array_key_exists($id, $this->poetry) )
				$title = _T('playlisy-poetryMightRemoved');

			$list[] = "
				<div class='clearfix'>
					<div class='tool'>
						<span role='sn' class='sn' no='{$v['id']}'>{$sn}. </span>
						<span role='cross' class='cross' no='{$v['id']}'>" . theme::icon('times') . "</span>
						<span role='grab'  class='grab'>" . theme::icon('arrows-v') . "</span>
					</div>
					<div class='title'>"
						. lib::htmlChars( $title ) . "
						<span class='hint'> (" . _T('notation-type' . $v['type'] ) . ")</span>
					</div>
				</div>";

			$sn++;
		}

		$url['sort'] = $this->getSortItemLink( $this->playlist['id'] );

		echo
			'<div style="margin-bottom:10px">' . _T('playlist-sortPS') . '</div>' .
			'<div id="playlistEdit">'
				. theme::block( '', theme::lists($list) ) .
			'</div>';

		$url = json_encode($url);

		$lang = json_encode( array(
			'removeCf' => _T('removeCf')
		) );

		$js = <<<js

			$(function(){
				var url = {$url},
				lang	= {$lang};

				$('[role="cross"]').click(function(){

					if ( !confirm(lang.removeCf) ) return;

					var no = $(this).attr('no');

					var _this = $(this);

					$.post(url[no].remove, {}, function(obj){
						if (obj.ret.status == false ) {
							alert(obj.ret.msg);
							return;
						}

						_this.closest('li').hide('fast', function(){
							$(this).remove();

							$('body').find('[role="sn"]').each(function(idx, itm){
								var sn = (idx+1) + '.';
								$(this).text(sn);
							});
						});

					}, 'json');
				});

				$('#playlistEdit ul').sortable({
					placeholder	: 'placeholder',
					items   	: 'li',
					handle		: '[role="grab"]',

					forcePlaceholderSize: true,
					opacity 	: .8,
					axis		: 'y',

					tolerance	: 'pointer',
					cursor  	: 'move',
					update		: function(e, ui){

						var items = new Array();

						$(this).find('[role="sn"]').each(function(idx, itm){
							var sn = (idx+1) + '.';
							$(this).text(sn);

							items.push( $(itm).attr('no') );
						});

						$.post(url.sort, {seq:items}, function(obj){
							if (obj.ret.status == false ) {
								alert(obj.ret.msg);
								return;
							}
						}, 'json');
					}
				});
			});
js;

		js::add( $js );
	}

	function vw_list(){
		$this->includeClass('list.php');

		$list = new CList('t1');

		$hdr = array(
			'id'		=> array('title' => _T('id'),					'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'title'		=> array('title' => _T('playlist-title'),		'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'poetry'	=> array('title' => _T('playlist-poetryCnt'),	'width' => '50px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'=> array('title' => _T('update'),				'width' => '80px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'allow'			=> array('id', 'title', 'poetry', 'lastUpdate'),
				'order' 		=> $this->order,
				'precedence'	=> $this->precedence,
				'url'			=> $this->getlink( 'all' )
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v) $align[$k] = $v['cAlign'];

		$list->setDataAlign($align);

		$url = array();

		foreach ( $this->playlists as $v ){

			$priv = '';
			if ( $v['priv'] == $this->close ) 
				$priv = ' <span class="hint">( ' . _T('playlist-priv') . ' )</span>';
			
			$list->setData(array(
				'id'			=> ( $v['id'] ) ? $v['id'] : '',
				'title'			=> theme::a( lib::htmlChars($v['title']), $this->getlink( $v['id'] )) . $priv,
				'poetry'		=> ( $v['poetry'] == 0 ) ? '-' : number_format( $v['poetry'] ),
				'lastUpdate'	=> lib::dateFormat( $v['lastUpdate'], 'm-d')
			));
		}

		echo theme::header(lib::htmlChars( _T('playlist') ));
		echo theme::block( '', $list->Close() );
	}

	public function editable( $id ){
		$playlist = array_shift( $this->get( $id ) );
		return (profile::$id == $playlist['creator']) ? true : false;

	}

	public function vw_title(){

		$list = array();
		$list[] = '<span class="hint">By ' . lib::htmlChars( $this->user['name'] ) . ',</span>';

		$list[] = lib::timeSpan( $this->playlist['lastUpdate'] ) . ' ' .  '<span class="hint">' . _T('lastUpdate-short') . ',</span>';
		$list[] = number_format( $this->playlist['view'] ) . ' ' .  '<span class="hint">' . _T('playlist-view') . '</span>';

		$mgr = array();

		if ( $this->editable( $this->playlist['id'] ) ){

			$mgr[] = theme::a( _T('edit'),	'javascript:edit()' );
			$mgr[] = theme::a( _T('remove'), 'javascript:remove()' );

			$url['remove']	= $this->getRemoveLink( $this->_id );
			$url['edit']	= $this->getEditLink( $this->_id );

			$lang = json_encode( array(
				'removeCf'	=> _T('removeCf'),
				'edit' 		=> _T('edit')
			) );

			$web_root = WEB_ROOT;

			$url = json_encode( $url );

			$js =<<<JS
				var lang = {$lang},
					url = {$url};

				function edit( id ){
					ui.modal.show(lang.edit, url.edit, 600, 400);
				}

				function remove(){

					if ( !confirm(lang.removeCf) ) return;

					$.post(url.remove, {}, function(obj){
						if (obj.ret.status == false) {
							alert( obj.ret.msg );
							return;
						}

						window.location.href = '{$web_root}/playlist/my';

					}, 'json');
				}
JS;
			js::add( $js );
		}

		echo theme::header(
			lib::htmlChars( $this->playlist['title'] ),
			'<div class="pull-left">'  . theme::grid($list) . '</div>' .
			'<div class="pull-right">' . theme::grid($mgr)  . '</div>'
		);
	}

	function vw_start(){

		if ( $this->playlist['priv'] == $this->close ) 
			echo theme::block('', _T('playlist-privPS'));

		echo theme::block('',
			"<div>
				<button id='embed-start' type='button' class='btn btn-info'>" . _T('playlist-embed-start') . "</button>
				<button id='image-gallery-button' type='button' class='btn btn-primary' style='margin-left:20px'>" . _T('playlist-start') . "</button>
			</div>
			<div style='margin-top:50px'>
				" . _T('playlist-filter') . " :
					<label style='cursor:pointer; margin-right:20px'><input name='filter' role='filter' value='lyric'		type='radio' checked/> " . _T('poetry-lyric')	. "</label>
					<label style='cursor:pointer; margin-right:20px'><input name='filter' role='filter' value='embed'		type='radio' /> " . _T('poetry-embed')		. "</label>
					<label style='cursor:pointer; margin-right:20px'><input name='filter' role='filter' value='notation'	type='radio' /> " . _T('notation')		. "</label>
					<label style='cursor:pointer; margin-right:20px'><input name='filter' role='filter' value='note'		type='radio' /> " . _T('poetry-note')		. "</label>
			</div>
			"
		);

		$web_root = WEB_ROOT;
		
		js::add(<<<js

		$(function(){
			$('#embed-start').click(function (event) {
				window.location.href = '{$web_root}/embed/{$this->_id}'
			});
			
			$('#image-gallery-button').on('click', function (event) {
				event.preventDefault();
				blueimp.Gallery($('#links .notation a'), $('#blueimp-gallery').data());
			});

			$('[role="filter"]').click(function(){

				if ( $('[role=' + $(this).val() + ']').is(':visible') ) return;

				$('[role="lyric"]').hide('fast');
				$('[role="embed"]').hide('fast');
				$('[role="notation"]').hide('fast');
				$('[role="note"]').hide('fast');

				$('[role=' + $(this).val() + ']').toggle('fast');
			});
		});
js
);
	}

	function vw_play(){

		$list = array();
		$sn1 = 1;

		$web_root = WEB_ROOT;

		$notationObj = prog::get('mod_notation');

		foreach ( $this->items as $v ){

			list($page, $id) = lib::parsePageId( $v['pageId'] );
			$notation = array();

			if ( !array_key_exists($id, $this->poetry) ) {

				$list[] = array(
					'prop' => array('style' => 'float:none', 'class' => 'col-md-6'),
					'html' => "
							<div class='clearfix item'>
								<div class='title'>
									" . $sn1 . '. ' .  _T('playlisy-poetryMightRemoved') . "
								</div>
							</div>"
				);

				$sn1++;
				continue;
			}

			$sn2 = 1;

			if ( !$notationObj->readable( $v['type'] ) ) {
				$notation[] = _T('notation-noPermission');
			} else {

				if ( count( $this->notation[ $id ][ $v['type'] ] ) == 0 ) {
					$notation[] = _T('playlisy-notationMightRemoved');
				} else {

					foreach ( $this->notation[ $id ][ $v['type'] ] as $v2 ) {

						$notation[] = array(
								'prop' => array('style' => 'float:none', 'class' => 'col-md-4 item'),
								'html' =>
									theme::a(
										"<img src='{$web_root}{$v2['path']}' / >",
										"{$web_root}{$v2['path']}",
										'',

										array(
											'data-gallery'	=> '',
											'title'			=> "{$sn1} " . lib::htmlChars( $this->poetry[$id]['title'] ) . "-{$sn2}. " .  lib::htmlChars($v2['srcName'])
										)
								)
								. '<div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' . lib::htmlChars($v2['srcName']) . '</div>'
							);

						$sn2++;
					}
				}
			}

			$lyric = $embed = $note = '';

			if ( $this->poetry[$id]['lyric'] ) $lyric = '<div role="lyric" class="lyric">' . nl2br( lib::htmlChars($this->poetry[$id]['lyric']) ) . '</div>';
			if ( $this->poetry[$id]['embed'] ) $embed = "<div style='display:none' size='{$this->poetry[$id]['width']}x{$this->poetry[$id]['height']}' role='embed' class='embed'>" . $this->poetry[$id]['embed'] . '</div>';
			if ( $this->poetry[$id]['note']  ) $note  = "<div style='display:none' role='note' class='note'>" . $this->poetry[$id]['note'] . '</div>';

			$list[] = array(
				'prop' => array('style' => 'float:none', 'class' => 'col-xs-12 col-md-6'),
				'html' => "
					<div class='clearfix item' style='padding:0 5px'>
						<div class='title' style='font-size:1.2em'>
							" . theme::a( theme::icon('external-link'), WEB_ROOT . "/poetry/{$id}", 'blank' ). " {$sn1}.
							" .  lib::htmlChars( $this->poetry[$id]['title'] ) . " <span class='hint'>(" . _T('notation-type' . $v['type'] ) . ")</span>
						</div>
						" . $lyric . "
						" . $embed . "
						" . $note  . "
						<div role='notation' class='clearfix notation' style='display:none'>" . theme::grid($notation) . "</div>
					</div>"
			);

			$sn1++;
		}

		js::addLib('http://blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js');
		js::addLib(JS_PATH  . '/bootstrap-image-gallery.min.js');

		js::add('
			$(function(){
				$("#blueimp-gallery").data("useBootstrapModal", false);
			});
		');

		echo '
			<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls">
				<div class="slides"></div>
				<h3 class="title"></h3>
				<a class="prev">‹</a>
				<a class="next">›</a>
				<a class="close">×</a>
				<ol class="indicator"></ol>
			</div>';


		$js = <<<embed

			$(function(){

				$(window).bind('resize orientationchange', function(){
					embedResize();
				});

				embedResize();

				function embedResize(){

					$.each( $('[role="embed"]'), function(k, v){

						var size = $(v).attr('size').split('x');

						$(v).width('100%');

						$(v).height(
							$('[role="embed"]').width() * size[1] / size[0]
						);

					} );
				}
			});
embed;

		js::add( $js );


		echo '<div id="links" class="playlistGrid">' . theme::block('', theme::grid($list) ) . '</div>';
	}

	public function getRemoveItemLink( $id ) {
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.playlist/playlist.removeItem", array('id'=> $id));
	}

	public function getSortItemLink( $id ) {
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.playlist/playlist.sort", array('id'=> $id));
	}

	function remove( $id ){

		$playlist = array_shift( $this->get( $id ) );

		if ( !$playlist ) {
			return array('status' => false, 'msg' => _T('playlist-notExist'));
		}

		event::trigger('playlist.before.remove', array('id' => $id));

		db::query('DELETE FROM playlist			WHERE id=:id',				array(':id'			=> $id));
		db::query('DELETE FROM playlist_item	WHERE parentID=:parentID',	array(':parentID' 	=> $id));

		event::trigger('playlist.after.remove', array('id' => $id));


		log::save('playlist', "remove {$playlist['title']}", profile::$id);

		return array('status' => true);
	}

	function removeItem( $id ){

		$item = array_shift( $this->getItem( $id ) );

		if ( !$item ) {
			return array('status' => false, 'msg' => _T('playlist-notExist'));
		}

		db::query('DELETE FROM playlist_item WHERE id=:id',	array(':id'	=> $id));

		db::query('UPDATE playlist AS p SET poetry=(SELECT COUNT(*) FROM playlist_item WHERE parentID=p.id) WHERE id=:id', array(
			':id' => $item['parentID']
		));

		return array('status' => true);
	}

	public function ax_sort(){

		$seq	= args::get('seq',  'nothing');
		$sn		= 10;

		foreach ( $seq as $v ){

			db::query('UPDATE playlist_item SET sn=:sn WHERE id=:id',
				array(
					':sn' => $sn,
					':id' => $v
				)
			);

			$sn += 10;
		}

		echo $this->ajaxReturn(true);
	}

	public function ax_remove(){

		$id  = args::get('id', 'int');
		$ret = $this->remove( $id );

		echo $this->ajaxReturn($ret['status'], $ret['msg']);
	}

	public function ax_removeItem(){

		$id  = args::get('id', 'int');
		$ret = $this->removeItem( $id );

		echo $this->ajaxReturn($ret['status'], $ret['msg']);
	}

	function vw_selector(){

	//	if ( count($this->playlist) == 0 ) return;

		$this->includeClass('list.php');

		$list = new CList('t1', 'radio');

		$hdr = array(
			'ck'			=> array('title' => '',						'width' => '40px',  'align' => 'center',	'cAlign' =>	'center'),
			'title'			=> array('title' => _T('playlist-title'),	'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'status'		=> array('title' => _T('playlist-already'),	'width' => '120',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'	=> array('title' => _T('update'),			'width' => '60px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr);

		$align = array();
		foreach ($hdr as $k=>$v) $align[$k] = $v['cAlign'];
		$list->setDataAlign($align);

		foreach ($this->playlist as $k=>$v){

			$status = '<span class="hint">-</span>';
			if ( count( $this->status[$v['id']] ) != 0 ) {
				$expo = array();
				foreach ($this->status[$v['id']] as $k2=>$v2){
					$expo[] = _T('notation-type' . $k2);
				}

				$status = join(', ', $expo);
			}

			$list->setData( array(
				'ck'			=> $v['id'],
				'title'			=> lib::htmlChars($v['title']),
				'status'		=> $status,
				'lastUpdate'	=> lib::timeSpan( $v['lastUpdate'] )
			));
		}

		echo theme::block( _T('playlist-addItemToList'), $list->Close() );
	}

	function vw_addItem(){ echo theme::block('', $this->form['poetry']);  }
	function vw_creator(){ echo theme::block('', $this->form['creator']); }

	public function vw_panel(){

		$formObj = json_encode( array(
			'title'			=> CForm::getId( $this, 'title' ),
			'createNew'		=> CForm::getId( $this, 'createNew' ),
			'playlistId'	=> CForm::getId( $this, 'playlistId' )
		) );

		$js = <<<JS

			$(function(){

				var form = {$formObj};

				toggleTitle();

				$('#t1 [type="radio"]').click(function(){
					$('#' + form.playlistId).val( $(this).val() );
				});

				$('#' + form.createNew).click(function(){
					toggleTitle();
				});

				function toggleTitle(){

					$('#' + form.title).prop( 'disabled', !$('#' + form.createNew).prop('checked') );
					$('#' + form.title).focus();

					$('#t1 [type="radio"]').prop({
						disabled: false
					});

					if ( $('#' + form.createNew).prop('checked') ) {
						$('#' + form.playlistId).val('');

						$('#t1 [type="radio"]').prop({checked:false, disabled:true});
						$('#t1 .selected').removeClass('selected');

					} else {
						$('#t1 [type="radio"]').first().trigger('click');
						$('#' + form.playlistId).val( $('#t1 [type="radio"]').first().val() );
					}
				}
			});

JS;
		js::add( $js );

		echo '<div>' . CForm::addSubmit() . '</div>';

		CForm::submit( WEB_ROOT . '/playlist/addItem', '', 'addItem', lib::makeAuth( array( WEB_ROOT . '/playlist/addItem', 'addItem') ));
		CForm::render();
	}

	public function getCnt(){
		$total = db::fetch('SELECT COUNT(*) AS cnt FROM playlist');

		return array('total' => $total['cnt']);
	}
}