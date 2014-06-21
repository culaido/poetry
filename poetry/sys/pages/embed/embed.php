<?php
Class embed extends Page {

	private $doc;

	private $open	= 0;
	private $close	= 1;

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

	function priv($param, $action) {
		return $action;
	}

	public function exec($param, $action){

		$this->_id = $param[0];

		$this->playlist = array_shift( prog::get('playlist')->get( $this->_id ) );

		$items = db::select('SELECT * FROM playlist_item WHERE parentID=:parentID ORDER BY sn ASC', array(
			':parentID' => $this->_id
		));

		$this->breadcrumb = array();
		$this->breadcrumb[] = array('title' => theme::a( lib::htmlChars( _T('playlist') ), prog::get('playlist')->getLink('all') ));
		$this->breadcrumb[] = array('title' => theme::a( lib::htmlChars( $this->playlist['title'] ), prog::get('playlist')->getLink( $this->playlist['id'] ) ));

		$poetry = $poetrys = array();

		while ( $obj = $items->fetch() ) {

			$this->items[] = $obj;

			list( $page, $id ) = lib::parsePageId($obj['pageId']);
			$poetry[ $id ] = $id;
		}

		$poetrys = prog::get('poetry')->get( $poetry );

		foreach ( $poetrys as $v ) {
			if ( !$v['url'] ) continue;
			$this->poetry[] = $v;
		}

		$list = array();
		foreach ( $this->poetry as $v ) {
			parse_str( parse_url( $v['url'], PHP_URL_QUERY ), $vars );
			$list[] = array(
				'id'		=> $vars['v'],
				'width' 	=> $v['width'],
				'height'	=> $v['height'],
				'title'		=> $v['title'],
				'lyric'		=> $v['lyric']
			);
		}

		$this->items = $list;

		$data = array(
			'type' => 'html',
			'html' => array(
				'title'		=> $this->playlist['title'],
				'layout'	=> 'base',
				'template'	=> 'playlist'
			),
			'content' => array()
		);
		
	js::add(<<<js

		$(document).ready(function() {
			$('[data-toggle=offcanvas]').click(function() {
				$('.row-offcanvas').toggleClass('active');
			});
		});
js
);
		$this->App->DOC->set('page', $data);

	}

	public function vw_title(){ echo theme::header( lib::htmlChars( $this->playlist['title'] ) ); }

	public function vw_show(){

		$items = json_encode( $this->items );

		$js = <<<js
			var vlist = {$items};
			var curr  = 0;

			var tag = document.createElement('script');

			tag.src = "https://www.youtube.com/iframe_api";

			var firstScriptTag = document.getElementsByTagName('script')[0];
			firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

			var player;

			function onYouTubeIframeAPIReady() {

				player = new YT.Player('player', {
					width: '100%',
					videoId: vlist[curr].id,
					events: {
						'onReady'		: onPlayerReady,
						'onStateChange'	: onPlayerStateChange
					}
				});

				lyricChange();

				playerResize();
			}

			function play()		{ player.playVideo();  }
			function pause()	{ player.pauseVideo(); }

			function onPlayerReady(event) {

			}

			function onPlayerStateChange(event) {

				if ( event.data == YT.PlayerState.ENDED ) {
					if ( !$('[role="autoNext"]').prop('checked') ) return;

					playNext();
				}
			}

			function playNext(){
				curr  += 1;
				if ( !vlist[curr] ) curr = 0;
				switchVideo( curr );
			}

			function switchVideo( id ){

				player.loadVideoById( vlist[id].id );
				curr = id;

				lyricChange();

				$.Evt('video.changed').publish( id );
				playerResize();
			}

			function playerResize(){
				var height = vlist[curr].height * $('#player').width() / vlist[curr].width;
				$('#player').height(height);
			}

			function lyricChange(){
				$('#title').html( vlist[curr].title );
				$('#lyric').html( vlist[curr].lyric );
			}
js;
			js::add( $js );


		echo theme::block('', '
			<div style="margin-bottom:30px">
				<div id="player"></div>
				<button onclick="javascript:play()"  class="btn btn-default btn-sm">' . theme::icon('play') . '</button>
				<button onclick="javascript:pause()" class="btn btn-default btn-sm" style="margin-left:5px">' . theme::icon('pause') . '</button>

				<label style="margin:5px 0 0 10px; cursor:pointer">
					<input role="autoNext" type="checkbox" checked> ' . _T('embed-autoNext') . '
				</label>
			</div>

			<div id="title" style="font-size:1.15em; font-weight:bold; border-bottom:1px solid #ccc"></div>
			<div id="lyric" style=""></div>
		');
	}


	public function vw_list(){

		$list	= array();
		$sn		= 0;

		foreach ( $this->items as $k=>$v ){
			$sn++;
			$list[] =
				"<div role='poetry' itemId='{$k}'>"
					.  theme::a( lib::htmlChars( $sn . '. ' . $v['title'] ) , "javascript:switchVideo( {$k} )") .
				"</div>";
		}

		echo theme::block(_T('poetry'), '<div class="embed">' . theme::lists($list) . '</div>' );

		$js = <<<js

			$(function(){
				$('[itemId="0"]').addClass('curr');

				$.Evt('video.changed').subscribe( function(id){
					$('[role="poetry"]').removeClass('curr');
					$('[itemId="' + id + '"]').addClass('curr');
				} );
			});
js;
		js::add( $js );
	}
}