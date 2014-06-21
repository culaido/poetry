<?php

Class comment extends Page {

	private $doc;

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

	function formSave(){

		if ( !self::$_pageMode == 'edit') return;

		$id = $this->getArgs('id', 'int');

		$data = array(
			'title'		=> $this->getArgs('title',		'string'),
			'note'		=> $this->getArgs('note',		'editor')
		);

		$this->save($id, $data);

		$from = $this->getArgs('from', 'nothing', $this->getLink($id) );

		CForm::onSuccess('parentUrl', $from);
	}

    function exec($param, $action) {

		if ( $action == 'add' ) {

            $auth		= args::get('auth',		'nothing');

            if ($auth != lib::makeAuth( array( $action, profile::$id ) ) ) return;

            $comment = $this->create();
            $comment = $comment['data'];

            $this->_id		= $comment['id'];
            $editMode_url	= $this->getEditLink( $this->_id );

            url::redir($editMode_url);
            exit();
		}

		$this->_id = (int) $param[0];

        if ( self::$_pageMode == 'edit' ) {
			require("action/edit.inc");  // include file to prepare page data
        }
		else {

			if ( $action == '' ) $action = 'base';
			$action_file = PAGE_PATH . "/comment/action/{$action}.inc";

			if (file_exists($action_file)) require($action_file);
		}
	}

	public function create( $param=array() ){

		$param['creator'] = $param['updater'] = profile::$id;
		$param['lastUpdate'] = date('Y-m-d H:i:s');

		db::query('INSERT INTO comment SET updater=:updater, creator=:creator, lastUpdate=NOW(), uploadComplete=:uploadComplete', array(
			':uploadComplete' => _UPLOAD_COMPLETE::notComplete,
			':creator' => $param['creator'],
			':updater' => $param['updater']
		));

		$param['id'] = db::lastInsertId();

		return array('status' => true, 'data' => $param);
	}

	public function save($id, $param){

		$data = array();

		$param['uploadComplete']	= _UPLOAD_COMPLETE::complete;
		$param['updater']			= profile::$id;

        foreach ($param as $idx=>$val){

            $data['qry'][]   = "{$idx}=:{$idx}";
            $data['param'][":{$idx}"] =  $val;
        }

        $data['param'][':id'] = $id;

        db::query("UPDATE comment SET " . join(', ', $data['qry']) . ", lastUpdate=NOW()  WHERE id=:id", $data['param']);

        return array('status' => TRUE, 'data' => $param);
	}

	private $_getInfo;
	public function get($id, $type = 'id', $args=array())	{
        if (!is_array($id)) $id = array($id);

        if (count($id)==1 && $this->_getInfo[$type][$id[0]]){
            return array($id[0] => $this->_getInfo[$type][$id[0]]);
        }

		$comment = db::select("SELECT * FROM comment WHERE FIND_IN_SET({$type}, ?) {$order}", array(join(',', $id)));

        $ret = array();
		while ($obj = $comment->fetch()) {
			$this->_getInfo[$type][$obj['id']] = $obj;
            $ret[ $obj['id'] ] = $this->_getInfo[$type][$obj['id']];
		}

		return $ret;
	}

	public function getLink( $id='' ){
		return WEB_ROOT . "/comment/{$id}";
	}

	public function getAddLink(){
		return WEB_ROOT . "/comment/add/?auth=" . lib::makeAuth( array( 'add', profile::$id ) );
	}

	public function getEditLink( $id ){
		return lib::lockUrl(WEB_ROOT . "/comment/{$id}/", array('_pageMode'=>'edit', 'id' => $id));
	}

	public function getRemoveLink( $id ){
		return lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.comment/comment.remove", array('id'=> $id));
	}


	public function vw_title() {

		$addLink = '';

		if ( priv::check('comment', 'editable') ){

			$lang	= json_encode(array('add' => _T('comment-add')));
			$url	= json_encode(array('add' => $this->getAddLink()));

			$js = <<<js

				function addComment(){
					var lang	= {$lang};
					var url		= {$url};

					ui.modal.show(lang.add, url.add, 600, 650);
				}
js;
			js::add( $js );

			$addLink = theme::a( _T('comment-add'), 'javascript:addComment()' );
		}

		echo theme::header( lib::htmlChars( $this->doc['title']), '<div class="pull-right">' . $addLink . '</div>' );
	}

	public function vw_list() {

		$this->includeClass('list.php');

		$list = new CList('t1');

		$hdr = array(
			'title'			=> array('title' => _T('comment-title'),		'width' => '',		'align' => 'left',	'cAlign' =>	'left'),
			'lastPostTime'	=> array('title' => _T('comment-lastPostTime'),	'width' => '250px',	'align' => 'left',	'cAlign' =>	'left')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'order'			=> $this->order,
				'allow'			=> array('id', 'title', 'lastPostTime'),
				'precedence'	=> $this->precedence,
				'url'			=> WEB_ROOT . "/comment/"
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v) $align[$k] = $v['cAlign'];

		$list->setDataAlign($align);

		$url = array();


		foreach ( $this->comments as $v ){

			$lastComment = '-';
			if ( $v['lastPoster'] ){

				$lastComment = "
					<span class='hint'>
						" . date('m-d', $v['lastPostTime']) . ", by " . lib::htmlChars(
							$this->lastPoster[ $v['lastPoster'] ]['name'] )
						. " : " . lib::htmlChars( trim( $v['lastComment'] ) ) . "
					</span>";
			}

			$list->setData(array(
				'title'			=> theme::a( lib::htmlChars($v['title']), $this->getLink( $v['id'] ) ),
				'lastPostTime'	=> $lastComment
			));
		}

		$args = array(
			"order="		. urlencode($this->order),
			"precedence="	. $this->precedence
		);

		echo theme::block('',
			$list->Close() .
			theme::page($this->currPage, $this->pageNum, $this->getLink() . "/?" . join('&', $args), 20)
		);
	}

	public function vw_show(){

		$item = array(); 
		
		if ( priv::check('comment', 'editable') ) {

			$lang	= json_encode(array(
				'edit'	 => $this->doc['title'],
				'remove'	=> _T('remove'),
				'removeCf'	=> _T('comment-removeCf')
			));

			$url	= json_encode(array(
				'comment'=> $this->getLink(),
				'edit'	 => $this->getEditLink( $this->_id ),
				'remove' => $this->getRemoveLink( $this->_id )
			));

			$js = <<<js

				var lang	= {$lang};
				var url		= {$url};

				function editComment(){
					ui.modal.show(lang.edit, url.edit, 600, 650);
				}

				function removeComment(){

					if ( !confirm( lang.removeCf) ) return;
					
					$.post(url.remove, {}, function(){
						window.location.href = url.comment;
					
					}, 'json');
				}
js;
			js::add( $js );

			$item[] = theme::a( _T('edit'), 'javascript:editComment()' );
			$item[] = theme::a( _T('remove'), 'javascript:removeComment()' );
		}

		$creator = lib::timeSpan( $this->doc['lastUpdate'] ) . ' ' .  '<span class="hint">' . lib::htmlChars($this->creator['name']) . ' ' . _T('lastUpdate-short') . '</span>';

		echo theme::header( $this->doc['title'],
			'<div class="pull-left">' . $creator . '</div>' .
			'<div class="pull-right">' . join('&nbsp;&nbsp;|&nbsp;&nbsp;', $item) . '</div>'
		);

		echo theme::block( '', $this->doc['note'] );
	}

	public function vw_comment(){

		$urls = json_encode( array(
			'update' => lib::lockUrl(WEB_ROOT . "/ajax/sys.pages.comment/comment.update", array('id'=> $this->_id)),
		) );

		$js = <<<js
			var disqus_shortname = '{$this->shortname}'; // required: replace example with your forum shortname

			(function() {
				var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
				dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
				(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
			})();

			var urls = {$urls};

			function disqus_config() {

				this.callbacks.onNewComment = [function(comment) {
					$.post(urls.update, {comment:comment.text}, function(){}, 'json');
				}];

				this.callbacks.onReady = [function() {

					$('[data-action="delete"]').click(function(){
						alert(1);
					});
				}];


			}
js;

		js::add( $js );

		echo theme::block('', '<div id="disqus_thread"></div><noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>');
	}

	public function ax_update(){

		$id = args::get('id', 'int');
		$comment = args::get('comment', 'editor');

		$this->save( $id, array(
			'lastComment'	=> $comment,
			'lastPostTime'	=> date('Y-m-d H:i:s'),
			'lastPoster'	=> profile::$id
		) );

		echo $this->ajaxReturn(true);
	}

	public function remove( $id ){
		db::query('DELETE FROM comment WHERE id=:id', array(':id' => $id));
	}

	public function ax_remove(){

		$id = args::get('id', 'int');
		$this->remove( $id );

		echo $this->ajaxReturn(true);
	}

	public function vw_edit(){
		echo theme::block('', join('', $this->form));
	}

}