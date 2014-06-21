<?php

Class tag Extends Page {

    function exec($param, $action) {
		$this->_tags = explode('-', args::get('tags', 'nothing'));
		$this->tonalitys	= prog::get('poetry')->getTonality();

		$this->tags = $this->getAll();

		$this->page = args::get('page', 'int', 1);

		$this->includeClass('sqllist.php');

		$sql = array();
		$param = array();

		foreach ( $this->_tags as $i=>$v ) {
			$sql[] = "tags LIKE :tag{$i}";
			$param[":tag{$i}"] = "%,{$v},%";
		}

		$this->order		= args::get('order',		'white_list', array('no', 'title', 'view', 'tonality', 'lastUpdate'));
		$this->precedence	= args::get('precedence',	'white_list', array( 'ASC', 'DESC'));

		$this->poetrys = new SQLList(array(
			'query'		=> array(
				'sql'   => "SELECT * FROM poetry WHERE " . join(' AND ', $sql) . " ORDER BY {$this->order} {$this->precedence}",
				'param'	=> $param
			),
			'page' => $this->page,
			'size' => args::get('size', 'int', 30)
		));

		$data = array(
			'type' => 'html',
			'html' => array(
				'layout'	=> 'tag',
				'template'	=> 'tag'
			),
			'content' => array()
		);

		$this->App->DOC->set('page', $data);
		$this->doc = &$this->App->DOC->get('page.content');

		js::addLib(JS_PATH . '/select2.min.js');
		js::addLib(JS_PATH . '/select2_locale_zh-TW.js');

		css::addFile(CSS_PATH . '/select2.css');

		css::add("
			.select2-drop li {float:left; margin:10px;}
			.header .title{padding-bottom:5px; font-size:inherit}
		");

		js::add(<<<js

			$(document).ready(function() {
				$('[data-toggle=offcanvas]').click(function() {
					$('.row-offcanvas').toggleClass('active');
				});
			});
js
		);
	}

	public function get($id){
		return db::fetch('SELECT * FROM tag WHERE id=?', array($id));
	}

	public function getAll(){
		$tags = db::select('SELECT * FROM tag ORDER BY id ASC');

		$tag = array();
		while ( $obj = $tags->fetch() )
			$tag[ $obj['id'] ] = $obj;

		return $tag;
	}


	public function vw_title() {

		$opt = array();
		foreach ($this->tags as $v){
			if ( $v['cnt'] == 0 ) continue;
			$selected = ( in_array($v['id'], $this->_tags) ) ? 'selected' : '';
			$opt[] = "<option value='{$v['id']}' {$selected}>" . lib::htmlChars($v['name']) . "</option>";
		}

		$link = prog::get('mod_tag')->getlink();

		$js = <<<js

			$(function(){
				var tag = '{$tag}';
				$('#tagger').select2({width: "100%", allowClear: false}).on('change', function(e){
					var v = $(e.target).val();

					if ( !v ) { return; }

					window.location.href = '{$link}' + v.join('-');
				});
			});
js;

		js::add($js);

		echo theme::header('<select id="tagger" multiple>'. join($opt) .'</select>');

	}

	public function vw_list(){

		$this->includeClass('list.php');

		$list = new CList('t1');

		$hdr = array(
			'no'			=> array('title' => _T('poetry-no'),	'width' => '60px',  'align' => 'center',	'cAlign' =>	'center'),
			'title'			=> array('title' => _T('poetry-title'),	'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'view'			=> array('title' => _T('poetry-view'),	'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'tonality'		=> array('title' => _T('poetry-tonality'),	'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'	=> array('title' => _T('update'),		'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'media'			=> array('title' => _T('media'),		'width' => '60px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'order'			=> $this->order,
				'allow'			=> array('no', 'title', 'view', 'tonality', 'lastUpdate'),
				'precedence'	=> $this->precedence,
				'url'			=> prog::get('mod_tag')->getlink( $this->_tags )
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v) $align[$k] = $v['cAlign'];


		$list->setDataAlign($align);

		$url = array();
		$embedData = array();

		while ( $v = $this->poetrys->fetch() ){

			$embedCode = '';
			if ( $v['embed'] ) {
				$embedData[ $v['id'] ] = array( 'title' => $v['title'], 'embed' => $v['embed'] );
				$embedCode = "<span embedId='{$v['id']}' role='media' height='{$v['height']}' width='{$v['width']}' style='cursor:pointer'>" . theme::icon('youtube-play') . "</span>";
			}

			$list->setData(array(
				'no'		=> $v['no'],
				'title'		=> theme::a( lib::htmlChars($v['title']), WEB_ROOT . '/poetry/' . $v['id']),
				'view'		=> number_format( $v['view'] ),
				'tonality'	=> $this->tonalitys[ $v['tonality'] ]['title'],
				'lastUpdate'=> lib::dateFormat( $v['lastUpdate'], 'm-d'),
				'media'		=> $embedCode
			));
		}

		$link = prog::get('mod_tag')->getlink( $this->_tags );

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

		$link .= "&order={$this->order}&precedence={$this->precedence}";
		echo theme::block( '',
			$list->Close() .
			theme::page($this->poetrys->currPage, $this->poetrys->pageNum, $link)
		);
	}
}