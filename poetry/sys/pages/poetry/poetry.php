<?php

Class poetry extends Page {

	private $doc;

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

	function editable(){
		return Priv::check('poetry', 'editable');
	}

    function exec($param, $action) {

		if ( $action == 'add' ) {

            $folderID	= args::get('folderID', 'int');
            $auth		= args::get('auth',		'nothing');
            $from		= args::get('from',		'nothing');

            if ($auth != lib::makeAuth( array( $action, $folderID ) ) ) return;

            $data = array('folderID'  => $folderID);

            $poetry = $this->create($data);
            $poetry = $poetry['data'];

            $this->_id		= $poetry['id'];
            $editMode_url	= lib::lockUrl(WEB_ROOT . "/poetry/{$this->_id}/", array('_pageMode'=>'edit', 'from' => $from));

            url::redir($editMode_url);
            exit();
		}

		$this->_id = (int) $param[0];

        if ( self::$_pageMode == 'edit' ) {
			require("action/edit.inc");  // include file to prepare page data
        }
		else {
			if ( $action == '' ) $action = 'base';
			$action_file = PAGE_PATH . "/poetry/action/{$action}.inc";

			if (file_exists($action_file)) require($action_file);
		}
	}

	function formSave(){

		if ( !self::$_pageMode == 'edit') return;

		$id			= $this->getArgs('id', 'int');
		$folderID	= $this->getArgs('folderID', 'int', 0);

        $poetry		= array_shift( $this->get($id) );

		$data = array(
			'no'		=> $this->getArgs('no',			'int'),
			'title'		=> $this->getArgs('title',		'string'),
			'note'		=> $this->getArgs('note',		'editor'),
			'url'		=> $this->getArgs('url',		'string'),
			'lyric'		=> $this->getArgs('lyric',		'nothing'),
			'tonality'		=> $this->getArgs('tonality',		'int'),
			'transposition'	=> $this->getArgs('transposition',	'string'),
			'lang'		=> $this->getArgs('lang',		'int')
		);

		$this->save($id, $data);
		$this->purge();

		if ($poetry['folderID'] != $folderID)
			$this->moveTo($id, $folderID);

		$from = $this->getArgs('from', 'nothing');

		CForm::onSuccess('redir', ( $from != '' )
			? base64_decode($from)
			: $this->getLink($id)
		);

	}

    public function moveTo($id, $folderID) {

        $src	= array_shift( $this->get($id) );

        if (!$src) return array('status' => FALSE, 'msg' => _T('poetry-notExist'));

        if ($src['folderID'] == $folderID) return array('status' => TRUE);

        db::query('UPDATE poetry SET folderID=? WHERE id=?', array($folderID, $id));

        prog::get('folder')->poetryCount( array($src['folderID'], $folderID) );

        event::trigger('poetry.after.move', array('id' => $id));

        return array('status' => TRUE);
    }

	private $_getInfo;
	public function get($id, $type = 'id', $args=array())	{
        if (!is_array($id)) $id = array($id);

        if (count($id)==1 && $this->_getInfo[$type][$id[0]]){
            return array($id[0] => $this->_getInfo[$type][$id[0]]);
        }

		if ( $args['order'] != '' ) {
			$order = "ORDER BY {$args['order']} {$args['precedence']}";
		}

		$poetry = db::select("SELECT * FROM poetry WHERE FIND_IN_SET({$type}, ?) {$order}", array(join(',', $id)));

        $ret = array();
		while ($obj = $poetry->fetch()) {
			$this->_getInfo[$type][$obj['id']] = $obj;
            $ret[ $obj['id'] ] = $this->_getInfo[$type][$obj['id']];
		}

		return $ret;
	}

	public function purge() {
		$this->_getInfo = array();
	}


	public function createPath(){

	   do {
			$hash	= strtolower(md5(rand() . '|' . time()));
            $path	= '/' . SYSDATA_PATH . "/poetry/{$hash}";

            $exist	= db::fetch('SELECT path FROM poetry WHERE path=?', array($path));
        } while ($exist);

        sys::mkdir(ROOT_PATH . $path);

		return $path;
	}

    public function create($param) {

		$folder = array_shift( prog::get('folder')->get( $param['folderID'] ) );

        if ( $param['folderID'] != 0 && !$folder) { return array('status' => FALSE, 'msg' => _T('folderNotExist')); }

        db::query('INSERT INTO poetry SET
                        folderID=:folderID, creator=:creator,
                        uploadComplete=:uploadComplete,
						lastUpdate=NOW(),
                        createTime=NOW()', array(
            ':folderID'	=> $param['folderID'],
            ':creator'	=> profile::$id,
            ':uploadComplete' => _UPLOAD_COMPLETE::notComplete
        ));

        $param['id'] = db::lastInsertId();
        $param['path'] = $this->createPath("poetry." . $param['id']);

        db::query("UPDATE poetry SET path=:path WHERE id=:id", array(
				':path' => $param['path'],
				':id'	=> $param['id']
			)
		);

        return array('status' => true, 'data' => $param);
    }

    public function save($id, $param=array()) {

		$poetry = array_shift( $this->get($id) );

		if (!$poetry) return FALSE;

		if ( array_key_exists('url', $param) && defined('EMBEDLY_KEY') ) {

			if ( $param['url'] == '' ) $param['embed'] = '';
			
			if ( $param['url'] != $poetry['url'] ) {

				$this->includeClass('Embedly.php');

				try {
					$api = new Embedly\Embedly(array(
						'user_agent' => 'Mozilla/5.0 (compatible; poetry/poetry-app; culaido@gmail.com)',
						'key' => EMBEDLY_KEY
					));

					$oembed = (array) array_shift( $api->oembed(array(
						'urls' => array($param['url']),
						'maxwidth'  => 1920,
						'maxheight' => 1080
					)) );

					$param['url']		= $oembed['url'];
					$param['embed']		= $oembed['html'];
					$param['width']		= $oembed['width'];
					$param['height']	= $oembed['height'];

				} catch(Exception $e){

					lib::mail('culaido@gmail.com', $param['url'] . ' fail', print_r($e, 1));
					$param['url'] = '';
				}
			}
		}

        $param['updater']	= profile::$id;

		$data = array();

		$param['uploadComplete'] = _UPLOAD_COMPLETE::complete;

        foreach ($param as $idx=>$val){

            if (in_array($idx, array('id', 'path', 'creator'))) continue;

            $data['qry'][]   = "{$idx}=:{$idx}";
            $data['param'][":{$idx}"] =  $val;
        }

        $data['param'][':id']		= $id;
		$data['qry'][] = 'lastUpdate=NOW()';

        db::query("UPDATE poetry SET " . join(', ', $data['qry']) . "  WHERE id=:id", $data['param']);

		if ( $poetry['uploadComplete'] == _UPLOAD_COMPLETE::notComplete && $param['uploadComplete'] == _UPLOAD_COMPLETE::complete){
			event::trigger( 'poetry.after.add', array('id' => $id));
        } else {
			event::trigger( 'poetry.after.save', array('id' => $id));
		}

		$this->purge();

		$this->saveSearch($id);
        return array('status' => TRUE);
    }

    public function saveSearch($id) {

		$search = array();

		$poetry = array_shift( $this->get($id) );

        $search[] = $poetry['no'];
        $search[] = $poetry['title'];
        $search[] = $poetry['lyric'];
        $search[] = $poetry['transposition'];

        db::query('UPDATE poetry SET search=? WHERE id=?', array(trim(strip_tags(join(' ', $search))), $id));
	}

	public function vw_title(){

		$list = array();

		$list[] = lib::timeSpan( $this->doc['createTime'] ) . ' ' .  '<span class="hint">' . lib::htmlChars($this->creator['name']) . ' ' . _T('createDate-short') . ',</span>';
		$list[] = lib::timeSpan( $this->doc['lastUpdate'] ) . ' ' .  '<span class="hint">' . lib::htmlChars($this->updater['name']) . ' ' . _T('lastUpdate-short') . ',</span>';
		$list[] = number_format( $this->doc['view'] ) . ' ' .  '<span class="hint">' . _T('poetry-view') . '</span>';

		$mgr = array();
		if ( $this->editable() ){
			$mgr[] = theme::a( _T('edit'), $this->getEditLink( $this->_id ) );
			$mgr[] = theme::a( _T('remove'), 'javascript:poetryRemove()' );

			$link = $this->getRemoveLink( $this->_id );
			$lang = json_encode( array(
				'removeCf' => _T('removeCf')
			) );

			$web_root = WEB_ROOT;

			$js =<<<JS
				var lang = {$lang};
				function poetryRemove(){

					if ( !confirm(lang.removeCf) ) return;

					$.post('{$link}', {}, function(obj){
						if (obj.ret.status == false) {
							alert( obj.ret.msg );
							return;
						}

						window.location.href = '{$web_root}/folder/{$this->doc['folderID']}';

					}, 'json');
				}
JS;
			js::add( $js );
		}

		echo theme::header(
			lib::htmlChars( $this->doc['title'] ),
			'<div class="pull-left">'  . theme::grid($list) . '</div>' .
			'<div class="pull-right">' . theme::grid($mgr)  . '</div>'
		);
	}

	public function vw_lyric(){
		echo theme::block('', '
			<div class="lyric">
				<div class="em">' . _T('poetry-lyric') . '</div>'

				. nl2br( lib::htmlChars($this->doc['lyric']) ) .
			'</div>');
	}


	public function vw_meta(){

		$meta = array();

		if ($this->doc['no']) $meta[] = array(_T('poetry-no') . " :", $this->doc['no']);

		$meta[] = array(
			_T('poetry-tonality'). " :",
			lib::htmlChars($this->tonality['title'])
				. '&nbsp;&nbsp;&nbsp;' .
			lib::htmlChars($this->doc['transposition'])
		);

		if ($this->doc['folderID']) $meta[] = array(_T('folder'). " :", theme::a( lib::htmlChars($this->folder['title']), WEB_ROOT . "/folder/{$this->folder['id']}") );


		foreach ( $meta as $v ){
			$html[] = "
			<div class='col-md-6'>
				<div class='pull-left em' style='width:50px'>{$v[0]}</div>
				<div style='white-space:nowrap; overflow:hidden; text-overflow:ellipsis'>{$v[1]}</div>
			</div>";
		}

		echo '<div class="row meta clearfix">' . theme::block('', join('', $html) ) . '</div>';
	}

	public function vw_note(){
		if ( trim( strip_tags($this->doc['note'], '<img>') ) == '' ) return;
		echo theme::block(_T('poetry-note'), $this->doc['note']);
	}

	public function vw_embed(){

		if ( !$this->doc['embed'] ) return;

		$size = json_encode( array( 'width' => $this->doc['width'], 'height' => $this->doc['height'] ) );

		$js = <<<embed

			$(function(){

				$(window).bind('resize orientationchange', function(){
					embedResize();
				});

				embedResize();

				function embedResize(){

					var size = {$size};

					$('[role="embed"]').width('100%');
					$('[role="embed"]').height(
						$('[role="embed"]').width() * size.height / size.width
					);
				}
			});
embed;

		js::add( $js );

		echo theme::block(_T('poetry-media'), '<div role="embed" class="embed">' . $this->doc['embed'] . '</div>');
	}

	public function vw_editInfo(){
		echo theme::block(_T('info'), _T('poetry-editInfo'));
	}


	public function vw_editTitle(){

		$title = _T('poetry-edit');

		if ( $this->doc['title'] ) $title = lib::htmlChars( _T('poetry-edit') . ' - ' . $this->doc['title'] );

		echo theme::header($title);
	}

	public function vw_edit(){

		echo theme::block('',
			"<div class='clearfix'>
				<div class='col-md-7'>"
					. join('', $this->form['left']) .
				"</div>

				<div class='col-md-5'>"
					. join('', $this->form['right']) .
				"</div>
			</div>"
		);
	}

	public function vw_panel(){

		echo
			"<div class='clearfix'>
				{$this->form['note']}
			</div>
			<div class='text-right'>"
				. CForm::addSubmit() .
					"&nbsp;&nbsp;&nbsp;"
				. CForm::addCancel() .
			"</div>";

	}

	public function getTonality( $id=0 ){

		if ($id != 0) {
			return db::fetch('SELECT * FROM tonality where id=:id', array(':id'=>$id));
		}

		$tonality = db::select('SELECT * FROM tonality ORDER BY sn ASC');

		$ret = array();
		while ( $obj = $tonality->fetch() ){
			$ret[ $obj['id'] ] = $obj;
		}

		return $ret;
	}


	public function getAddLink( $from, $folderID ) {
		$from = urlencode( $from );
		if (!$folderID) $folderID = 0;
		return WEB_ROOT . "/poetry/add/?from={$from}&folderID={$folderID}&auth=" . lib::makeAuth( array( 'add', $folderID ) );
	}

	public function getLink( $id ) {
		return WEB_ROOT . "/poetry/{$id}";
	}


	public function getEditLink( $id, $from = '' ) {
		return lib::lockUrl(WEB_ROOT . "/poetry/{$id}/", array('_pageMode'=>'edit', 'from' => $from));
	}

	public function getRemoveLink( $id ) {
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.poetry/poetry.remove", array('id'=> $id));
	}

	public function getRemoveListLink() {
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.poetry/poetry.removeList", array('profile'=> profile::$id));
	}

	public function getList(){

		$poetrys = db::select('SELECT * FROM poetry WHERE uploadComplete=:uploadComplete ORDER BY no ASC',
			array(':uploadComplete' => _UPLOAD_COMPLETE::complete)
		);

		$poetry = array();
		while ($obj = $poetrys->fetch()){
			$poetry[ $obj['id'] ] = $obj;
		}

		return $poetry;
	}

	function remove( $id ){

		$poetry = array_shift( $this->get( $id ) );

		if ( !$poetry ) {
			return array('status' => false, 'msg' => _T('poetry-notExist'));
		}

		event::trigger('poetry.before.remove', array('id' => $id));

		db::query('DELETE FROM poetry WHERE id=:id', array(':id' => $id));

		event::trigger('poetry.after.remove', array('folderID' => $poetry['folderID']));


		log::save('poetry', "remove {$poetry['title']}", profile::$id);

		return array('status' => true);
	}

	public function ax_remove(){

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


	function hk_onFolderRemove( $param ){

		ignore_user_abort(TRUE);

		$id = $param['id'];
		db::query('UPDATE poetry SET folderID=0 WHERE folderID=:folderID', array('folderID' => $id));
	}


	public function getCnt(){

		$total = db::fetch('SELECT COUNT(*) AS cnt FROM poetry WHERE uploadComplete=:uploadComplete', array(
			':uploadComplete' => _UPLOAD_COMPLETE::complete
		));

		return array('total' => $total['cnt']);
	}


}