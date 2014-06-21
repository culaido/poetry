<?php

Class mod_music Extends Module {

	public function get( $pageId ){
		$music = db::fetch('SELECT * FROM music WHERE pageId=:pageId', array(':pageId' => $pageId));

		if (!$music) $music = $this->create(array('pageId' => $pageId));

		return $music;
	}

	public function create( $param ){

		db::query('INSERT INTO music SET pageId=:pageId', array(':pageId' => $param['pageId']));

		$param['id'] = db::lastInsertId();

		return array('id' => $param['id'], 'pageId' => $param['pageId'], 'path' => '', 'srcName' => '');
	}

	public function save($id, $param){

		$data = array();

        foreach ($param as $idx=>$val){

            if (in_array($idx, array('id', 'account'))) continue;

            $data['qry'][]   = "{$idx}=:{$idx}";
            $data['param'][":{$idx}"] =  $val;
        }

        $data['param'][':id'] = $param['id'] = $id;

        db::query("UPDATE music SET " . join(', ', $data['qry']) . ", lastUpdate=NOW() WHERE id=:id", $data['param']);

        return array('status' => TRUE, 'data' => $param );
	}

	public function formSave(){

		$id	= $this->getArgs('id', 'int');

		$ret = $this->save($id, array(
			'srcName'	=> $this->getArgs('srcName', 'nothing'),
			'path'		=> $this->getArgs('path',	 'nothing'),
		));

		$path = $this->getArgs('path', 'nothing');

		$uploader	= prog::get('mod_fileUpload');
		$list		= $uploader->getList( 'mod_music.' . $id );

		foreach ($list as $v){
			if ( $v['path'] != $path )
				$uploader->remove( $v['id'] );
		}
	}

	public function ac_edit(){

		$this->pageId = $this->getPageId();
		$this->doc = $this->get( $this->pageId );

		$this->form['srcName']	= CForm::hidden($this, 'srcName',	$this->doc['srcName'], array());
		$this->form['path']		= CForm::hidden($this, 'path',		$this->doc['path'], array());

		$this->form['id']	= CForm::hidden($this, 'id', $this->doc['id']);

		$text = _T('music-change');

		$args = array(
			'multiple'					=> false,
			'acceptFileTypes'			=> array('mp3', 'ogg', 'wav'),
			'limitConcurrentUploads'	=> 1,
			'maxNumberOfFiles'			=> 1
		);

		$this->form['music'] = CForm::fieldset(
			_T('music'),
			'<div role="musicUploader" class="clearfix">
				<div class="pull-left" style="margin-right:10px">'
					. CForm::file($this, 'music', _T('music-accept'), 'mod_music.' . $this->doc['id'], $args) .
				'</div>
				<div role="musicProgress" class="progress" style="margin-top:5px">
					<div class="progress-bar progress-bar-info"></div>
				</div>
			</div>
			<div role="musicName" style="display:none; margin-bottom:5px"></div>',
			"<audio role='musicPlayer' src='' controls preload='none'></audio>"
		);
	}

	public function vw_edit(){

		$id			= CForm::getId($this, 'music');
		$srcName	= CForm::getId($this, 'srcName');
		$path		= CForm::getId($this, 'path');

		$lang = json_encode( array(
			'remove' => _T('remove')
		));

		$web_root = WEB_ROOT;
		$doc = json_encode( $this->doc );

		$js = <<<music
			var lang = {$lang};
			var doc  = {$doc};

			$(function(){
				loadMusic(doc);
			});

			function loadMusic( music ){

				if ( music['path'] == '' ) return;

				$('[role="musicUploader"]').hide('fast');

				var name = $('<span>', {
					'text' : music.srcName
				});

				var cross = $('<span>', {
							'style' : 'margin-left:5px; color:#f00; cursor:pointer',
							'class' : 'fa fa-times',
							'title' : lang.remove}
				).click(function(){
					removeMusic();
				});

				$('[role="musicName"]')
					.append(name)
					.append(cross)
					.show('fast');

				$('[role="musicPlayer"]').attr('src', '{$web_root }' + music.path);

				$('#{$srcName}').val( music.srcName );
				$('#{$path}').val( music.path );
			}

			function removeMusic( ){

				$('[role="musicProgress"] .progress-bar').css('width', 0);

				$('[role="musicUploader"]').show('fast');

				$('[role="musicPlayer"]').attr('src', '');
				$('[role="musicName"]').html('').show('fast');

				$('#{$srcName}').val( '' );
				$('#{$path}').val( '' );
			}

			$(function(){

				$.Evt('{$id}.progressall').subscribe(function(data){
					var progress = parseInt(data.loaded / data.total * 100, 10);
					$('[role="musicProgress"] .progress-bar').css('width', progress + '%');
				});

				$.Evt('{$id}.done').subscribe(function(data){
					var music = data[0];

					loadMusic(music);
				});

				$.Evt('{$id}.start').subscribe(function(data){
					$(this).prop('disabled', true);
				});
			});
music;
		js::add( $js );

		echo '<div class="music edit">' . theme::block('', join('', $this->form)) . '</div>';
	}

	function remove( $pageId ){

		$music = $this->get( $pageId );
		event::trigger('music.before.remove', array('id' => $music['id']));

		db::query('DELETE FROM music WHERE pageId=:pageId', array(':pageId' => $pageId));
	}

	function ac_show(){

		if ( !$this->readable() ) return;

		$this->pageId = $this->getPageId();
		$this->doc = $this->get( $this->pageId );

		if (!$this->doc) return;
	}

	function vw_show(){

		if ( !$this->readable() ) return;

		if ( !$this->doc ) return;
		if ( $this->doc['path'] == '' ) return;

		$path = ROOT_PATH . $this->doc['path'];

		if ( !file_exists($path) ) return;

		$name = lib::htmlChars( $this->doc['srcName'] );

		echo theme::block('', "
			<div style='margin-bottom:5px'>
				<audio src='" . WEB_ROOT . "{$this->doc['path']}' controls></audio>
			</div>"
		);
	}



	function readable(){
		return Priv::check('music', 'readable');
	
	}
	
	function hk_onPoetryRemove( $param ){

		ignore_user_abort(TRUE);

		$id = $param['id'];
		$this->remove('poetry.' . $id);
	}


}