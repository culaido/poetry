<?php

Class mod_notation Extends Module {
	public $type = array(1, 2);
	
	public function readable($type) {
		return Priv::check("notationType{$type}", 'readable');
	}

	public function get( $pageId, $type=1 ) {
		$notations = db::select('SELECT * FROM notation WHERE pageId=:pageId AND type=:type ORDER BY sn ASC',
			array(
				':pageId'	=> $pageId,
				':type'		=> $type
		));

		$notation = array();

		while ( $obj = $notations->fetch() ){
			$notation[] = $obj;
		}

		return $notation;
	}

	public function getUsable( $pageId ){

		$notation = array();

		foreach ( $this->type as $v ){

			if ( !$this->readable($v) ) continue;

			$obj = $this->get( $pageId, $v );

			if ($obj) $notation[ $v ] = $obj;
		}

		return $notation;
	}


	public function create( $param ){

		$max = db::fetch('SELECT MAX(sn) AS sn FROM notation WHERE pageId=:pageId AND type=:type', array(
			':pageId'	=> $param['pageId'],
			':type'		=> $param['type']
		));

		$param['sn'] = (int) $max['sn'] + 10;

		db::query('INSERT INTO notation SET
					pageId	= :pageId,
					type	= :type,
					srcName	= :srcName,
					path	= :path,
					sn		= :sn,
					lastUpdate = NOW()',
			array(
				':pageId'	=> $param['pageId'],
				':type'		=> $param['type'],
				':srcName'	=> $param['srcName'],
				':path' 	=> $param['path'],
				':sn'		=> $param['sn']
		));

		$param['id'] = db::lastInsertId();

		return array('status' => true, 'data' => $param);
	}


	public function formSave(){}

	public function ac_show( $param ){

		$this->pageId	= $this->getPageId();
		$type			= $param['args']['type'];

		if ( !$this->readable($type) ) return;
		css::addFile(CSS_PATH . '/lightbox.css');

		$this->notation[ $type ] = $this->get($this->pageId, $type);
	}

	public function vw_show( $param ){

		$type	= $param['args']['type'];

		if ( !$this->readable($type) ) return;
		if ( count( $this->notation[ $type ] ) == 0 ) return;

		$cnt	= count($this->notation[ $type ]);

		$list	= array();
		$sn		= 1;

		$web_root = WEB_ROOT;
		foreach ( $this->notation[ $type ] as $k=>$v ) {

			$gallery = ( $cnt > 1 ) ? "data-parent='ul' data-gallery='multiimages'" : '';

			
			$list[] = array(
				'prop' => array('class' => 'col-md-6'),
				'html' => "
					<div class='clearfix item'>
						<div class='image'>
							<a href='{$web_root}{$v['path']}' data-lightbox='notation' {$gallery} title='" . lib::htmlChars($v['srcName']) . "'>
								<img src='{$web_root}{$v['path']}' />
							</a>
						</div>
						<div class='name'>{$sn}." . lib::htmlChars($v['srcName']) . "</div>
					</div>"
			);

			$sn++;
		}

		js::addLib(JS_PATH . '/lightbox-2.6.min.js');
		js::addLib(JS_PATH . '/jquery.printPage.js');

		echo theme::block(
			_T('notation-type' . $type),
			'<div class="notationList row">'
				. theme::lists( $list ) .
			'</div>'
		);
	}

	public function ac_edit(){

		$this->pageId	= $this->getPageId();

		foreach ( $this->type as $v){
			$this->notation = 'notation-type' . $v;

			$this->doc[ $v ] = $this->get($this->pageId, $v);

			$this->form['sn']	= '';

			$args = array(
				'acceptFileTypes'			=> array('jepg', 'jpg', 'bmp', 'png', 'gif'),
				'limitConcurrentUploads'	=> 1
			);

			$this->form[$this->notation] = CForm::fieldset(
				_T( $this->notation ),
				'<div id="'.$this->notation.'">
					<div role="uploader" class="clearfix">
						<div class="pull-left" style="margin-right:10px">'
							. CForm::file($this, $this->notation, _T('notation-accept'), $this->pageId, $args) .
						'</div>
						<div role="progress" class="progress" style="margin-top:5px">
							<div class="progress-bar progress-bar-info"></div>
						</div>
					</div>
					<div role="images" type="'. $v .'"></div>
				</div>'
			);

		}
	}

	public function vw_edit(){

		$args = json_encode( $this->doc );
		$id	= CForm::getId($this, '');

		$url = json_encode( array(
			'post'		=> lib::lockUrl(WEB_ROOT . '/ajax/sys.modules.mod_notation/mod_notation.post',		array('pageId' => $this->pageId, 'id' => profile::$id) ),
			'remove'	=> lib::lockUrl(WEB_ROOT . '/ajax/sys.modules.mod_notation/mod_notation.remove',	array('pageId' => $this->pageId) ),
			'sort'		=> lib::lockUrl(WEB_ROOT . '/ajax/sys.modules.mod_notation/mod_notation.sort',		array('pageId' => $this->pageId, 'id' => profile::$id) )
		) );

		$lang = json_encode( array(
			'removeCf' => _T('removeCf')
		) );

		$web_root = WEB_ROOT;

		$js = <<<notation

		$(function(){

			var args = {$args},
				lang = {$lang};

			for ( var k in args ){

				notationBindEvt(k, 'notation-type' + k);

				for ( var key in args[k] ){
					createNotationItem('notation-type' + k, args[k][key]);
				}
			}

			$('body').on('click', '[role="notation-cross"]', function(){

				var id = $(this).attr('notationId');

				if ( !confirm(lang.removeCf) ) return;

				var _this = $(this);
				$.post(url.remove, {id:id}, function(obj){
					if (obj.ret.status == false ) {
						alert(obj.ret.msg);
						return;
					}

					_this.closest('[role="item"]').hide('fast', function(){
						$(this).remove();

						$('[role="images"]').each(function(){
							$(this).find('[role="sn"]').each(function(idx, itm){
								var sn = (idx+1) + '.';
								$(this).text(sn);
							});
						});

					});

				}, 'json');

			});

			$('[role="images"]').sortable({
				helper		: 'clone',
				handle		: 'img',
				placeholder	: 'placeholder',
				items   	: '[role="item"]',
				forcePlaceholderSize: true,
				opacity 	: .8,
				tolerance	: 'pointer',
				cursor  	: 'move',
				update		: function(e, ui){

					var seq = $(this).sortable('toArray');

					var items = new Array();
					for (var k in seq) {
						items.push( $('#' + seq[k]).attr('no') );
					}

					$(this).find('[role="sn"]').each(function(idx, itm){
						var sn = (idx+1) + '.';
						$(this).text(sn);
					});

					$.post(url.sort, {type:$(this).attr('type'), seq:items}, function(obj){
						if (obj.ret.status == false ) {
							alert(obj.ret.msg);
							return;
						}
					}, 'json');
				}

			});


			$('[role="images"]').each(function(){
				$(this).find('[role="sn"]').each(function(idx, itm){
					var sn = (idx+1) + '.';
					$(this).text(sn);
				});
			});
		});

		var url = {$url};

		function notationBindEvt(type, name){

			$.Evt('{$id}' + name + '.progressall').subscribe(function(data){
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$('#'+ name +' [role="progress"] .progress-bar').css('width', progress + '%');
			});

			$.Evt('{$id}' + name + '.done').subscribe(function(data){

				$.post(url.post, {type:type, data:data[0]}, function(obj){
					if (obj.ret.status == false ) {
						alert(obj.ret.msg);
						return;
					}

					createNotationItem(name, obj.ret.data);

				}, 'json');

			});
		}

		var notationItem = tmpl("<div no='{%=o.id%}' class='item' role='{%=o.role%}' id='notation{%=o.id%}'>\
			<div><img src='{%=o.img%}' /></div>\
			<div role='notation-cross' notationId='{%=o.id%}' class='cross fa fa-stack'>\
				<i class='fa fa-circle fa-stack-2x'></i>\
				<i class='fa fa-times fa-stack-1x fa-inverse'></i>\
			</div>\
			<div class='info'><span class='sn' role='sn'></span><span>{%=o.name%}</span></div>\
		</div>");

		function createNotationItem( place, data ){

			var item = notationItem({
				'id'	: data.id,
				'role'	: 'item',
				'img'	: '{$web_root}' + data.path,
				'name'	: data.srcName
			});

			$('#'+ place +' [role="images"]').append( item );

			$('#'+ place +' [role="images"]').find('[role="sn"]').each(function(idx, itm){
				var sn = (idx+1) + '.';
				$(this).text(sn);
			});
		}

notation;

		js::add( $js );

		echo '<div class="notation edit">' . theme::block('', join('', $this->form)) . '</div>';

	}

	function ax_sort(){

		$seq	= args::get('seq',  'nothing');
		$sn		= 10;

		foreach ( $seq as $v ){

			db::query('UPDATE notation SET sn=:sn WHERE id=:id',
				array(
					':sn' => $sn,
					':id' => $v
				)
			);

			$sn += 10;
		}

		echo $this->ajaxReturn(true);
	}

	function ax_post(){

		$type	= args::get('type', 'nothing');
		$data	= args::get('data', 'nothing');
		$pageId = args::get('pageId', 'nothing');

		$ret = $this->create(array(
			'pageId'	=> $pageId,
			'type'		=> $type,
			'srcName'	=> $data['srcName'],
			'path'		=> $data['path'],
		));

		echo $this->ajaxReturn(true, 'ok', array(), array( 'data' => $ret['data'] ));
	}


	function remove( $id ){
		db::query('DELETE FROM notation WHERE id=:id', array(':id' => $id));
	}

	function ax_remove(){

		$id = args::get('id', 'int');
		event::trigger('notation.before.remove', array('id' => $music['id']));

		$this->remove( $id );

		echo $this->ajaxReturn(true);
	}

	function hk_onPoetryRemove( $param ){

		ignore_user_abort(TRUE);

		$id = $param['id'];

		db::query('DELETE FROM notation WHERE pageId=:pageId', array(':pageId' => 'poetry.' . $id));
	}


}