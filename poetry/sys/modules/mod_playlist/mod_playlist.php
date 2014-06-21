<?php
Class mod_playlist Extends Module {
	private $open = 0;
	
	function ac_show(){
		$this->pageId = $this->getPageId();
		$this->notation = prog::get('mod_notation')->getUsable( $this->pageId );
	}

	function vw_show(){

		echo theme::block('',
			"<button id='playlistAdd' type='button' class='btn btn-primary'>" . _T('playlist-addItem') . "</button>"
		);

		$url = json_encode( array(
			'add' => prog::get('playlist')->getAddItemLink( $this->pageId )
		) );

		$lang = json_encode( array(
			'addItem' => _T('playlist-addItem')
		) );

		$js =<<<playlist

			$(function(){
				var url		= {$url};
				var lang	= {$lang};

				$('#playlistAdd').click(function(){
					ui.modal.show(lang.addItem, url.add, 600, 650);
				});
			});
playlist;

		js::add( $js );
	}

	public function get($id, $type='id'){
		return prog::get('playlist')->get($id, $type);
	}

	public function getLink($id){
		return prog::get('playlist')->getLink($id);
	}

	function ac_my(){
		$this->myPlaylist = db::select('SELECT * FROM playlist WHERE creator=:creator ORDER BY id DESC LIMIT 5', array(
			':creator' => profile::$id
		));
	}

	function vw_my(){

		$list = array();

		foreach ($this->myPlaylist as $v){
			$list[] =
			"<div class='clearfix'>" .
				'<div class="hint pull-right">' . number_format( $v['view'] ) . ' ' . _T('playlist-view') . '</div>' .
				'<div class="title">' . theme::a( lib::htmlChars($v['title']), $this->getLink($v['id']) ) . " <span class='hint'>(" . number_format( $v['poetry'] ) . ")</span></div>
			</div>";
		}

		if ( count($list) == 0 ) return;

		echo '<div class="myPlaylist">' . theme::block(_T('playlist-my'), theme::lists($list)) . '</div>';
	}

	function ac_list(){

		$this->newestPlaylist = db::select('SELECT * FROM playlist WHERE priv=:priv ORDER BY id DESC LIMIT 10', array(
			':priv'		=> $this->open
		));
	}

	function vw_list(){
		$list = array();

		foreach ($this->newestPlaylist as $v){
			$list[] =
			"<div class='clearfix'>" .
				'<div class="hint pull-right">' . number_format( $v['view'] ) . ' ' . _T('playlist-view') . '</div>' .
				'<div class="title">' . theme::a( lib::htmlChars($v['title']), $this->getLink($v['id']) ) . " <span class='hint'>(" . number_format( $v['poetry'] ) . ")</span></div>
			</div>";
		}

		if ( count($list) == 0 ) $list[] = _T('no data');

		$more = '';
		if ( count($list) >= 1 ) $more = '<span class="hint pull-right">' . theme::a('more', $this->getLink('all') ) . '</span>';

		echo '<div class="newestPlaylist">' . theme::block(
			_T('playlist-newest') . $more,
			theme::lists($list))
		. '</div>';
	}

	function ac_hotest($param){

		$limit = ($param['args']['limit']) ? $param['args']['limit'] : 5;
		$hotest = db::select("SELECT id, pageId, COUNT(pageId) AS cnt FROM playlist_item
						GROUP BY pageId
						ORDER BY cnt DESC LIMIT {$limit}");

		$poetryObj = prog::get('poetry');

		$this->hotest = array();
		while ($obj = $hotest->fetch()){

			list($page, $id) = lib::parsePageId( $obj['pageId'] );
			$poetry = array_shift( $poetryObj->get( $id ) );

			$this->hotest[ $id ] = $obj;
			$this->hotest[ $id ]['title'] = $poetry['title'];
		}
	}

	function vw_hotest(){

		$list = array();

		$poetryObj = prog::get('poetry');

		foreach ($this->hotest as $k=>$v){

			$list[] = "
				<div class='pull-right hint'>" . _T('playlist-times', array('%time%' => number_format( $v['cnt'] ) ) ) . "</div>
				<div class='title'>" . theme::a( lib::htmlChars($v['title']), $poetryObj->getLink($k)) . "</div>";
		}

		echo '<div class="hotestList">' . theme::block(_T('playlist-hotest'), theme::lists($list)) . '</div>';
	}


}