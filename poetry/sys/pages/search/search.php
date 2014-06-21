<?php

class search Extends Page {

    private $doc;

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

    function exec($param, $action) {

		$this->keyword = args::get('keyword', 'nothing', '');

		if ( $action == '' ) $action = 'base';
		$action_file = PAGE_PATH . "/search/action/{$action}.inc";

		if (file_exists($action_file)) require($action_file);
	}

	function vw_title(){
		echo theme::header(theme::icon('search') . ' ' . _T('search-result') . lib::htmlChars( $this->keyword ));
	}

	function vw_show(){

		$this->includeClass('list.php');

		$list = new CList('t1');

		$hdr = array(
			'no'		=> array('title' => _T('poetry-no'),		'width' => '60px',  'align' => 'center',	'cAlign' =>	'center'),
			'title'		=> array('title' => _T('poetry-title'),		'width' => '220px',	'align' => 'left',		'cAlign' =>	'left'),
			'search'	=> array('title' => _T('poetry-search'),	'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'tonality'	=> array('title' => _T('poetry-tonality'),	'width' => '50px',	'align' => 'center',	'cAlign' =>	'center'),
			'folderID'	=> array('title' => _T('folder'),			'width' => '120px',	'align' => 'center',	'cAlign' =>	'center'),
			'media'		=> array('title' => _T('media'),			'width' => '50px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr, array(
			'sort' => array(
				'order'			=> $this->order,
				'allow'			=> array('no', 'title', 'view', 'tonality', 'folderID', 'lastUpdate'),
				'precedence'	=> $this->precedence,
				'url'			=> WEB_ROOT . "/search/?" . join('&', array(
					'keyword='	. urlencode($this->keyword),
					'tonality='	. urlencode($this->tonality),
					'filter='	. implode( (array) $this->filter)
				))
			)
		));

		$align = array();
		foreach ($hdr as $k=>$v){
			$align[$k] = $v['cAlign'];
		}

		$list->setDataAlign($align);

		$embedData = array();
		while ( $v = $this->poetrys->fetch() ){

			$folder = ( $v['folderID'] != 0 )
				? theme::a( lib::htmlChars( $this->folders[ $v['folderID'] ]['title'] ), WEB_ROOT . '/folder/' . $v['folderID'] )
				: theme::a( lib::htmlChars( _T('folder-root') ), WEB_ROOT . '/folder/0' );

			$search = $v['search'];
			if ( $this->keyword ) {
				$start  = mb_strpos(trim(strtoupper($v['search'])), strtoupper($this->keyword), 0, 'utf8');
				$start -= 10;

				if ($start < 0) $start = 0;

				$search =  mb_substr($v['search'], $start, $start+50, 'utf8');
			}

			$embedCode = '';
			if ( $v['embed'] ) {
				$embedData[ $v['id'] ] = array( 'title' => $v['title'], 'embed' => $v['embed'] );
				$embedCode = "<span embedId='{$v['id']}' role='media' height='{$v['height']}' width='{$v['width']}' style='cursor:pointer'>" . theme::icon('youtube-play') . "</span>";
			}

			$list->setData(array(
				'no'		=> "<span role='highlighter'>{$v['no']}</span>",
				'title'		=> "<span role='highlighter'>" . theme::a( lib::htmlChars($v['title']), WEB_ROOT . '/poetry/' . $v['id']) . '</span>',
				'search'	=> "<span role='highlighter' title='".lib::htmlChars( $v['search'] ) ."'>{$search}</span>",
				'tonality'	=> $this->tonalitys[ $v['tonality'] ]['title'],
				'folderID'	=> $folder,
				'media'		=> $embedCode
			));
		}

		$keyword = json_encode( array('keyword' => explode(' ', $this->keyword) ) );

		$tonality = args::get('tonality', 'int');

		$selected[ $tonality ] = 'selected';

		$tonalitys = array();
		$tonalitys[] = '<option value="0" '.$selected[0].'>' . lib::htmlChars( _T('none') ) . '</option>';

		foreach ( $this->tonalitys as $v ){
			$tonalitys[] = '<option value="' . $v['id'] .'" '.$selected[$v['id']].'>' . lib::htmlChars( $v['title'] ) . '</option>';
		}

		$panel = array();

		$checked['title'] = $checked['lyric'] = '';
		if (!$this->filter) {
			$checked['title'] = $checked['lyric'] = 'checked';
		} else {
			foreach ( $this->filter as $v ) {
				$checked[$v] = 'checked';
			}
		}

		$panel[] = "
		<div style='margin:5px 0' class='col-xs-12 col-md-6'>"
			. _T('poetry-search') . " : <input type='search' role='searcher' onkeypress='searchOnKeypress(event)' placeholder='" . _T('search no, title, lyric') . "' style='height:28px'>
			<span role='filter' style='margin-left:10px'> (
				<label class='hint'><input value='title' type='checkbox' " . $checked['title'] . " /> " . _T('poetry-title') . "</label>
				<label class='hint' style='margin-left:10px'><input value='lyric' type='checkbox'  " . $checked['lyric'] . " /> " . _T('poetry-lyric') . "</label>
			) </span>
		</div>";

		$k = json_encode(array('text' => $this->keyword));
		$js =<<<search
			$(function(){
				var keyword = {$k};
				if ( keyword.text == '' ) $('[role="searcher"]').val( keyword ).focus();
				
				$('[role="searcher"]').val(keyword.text);
			});

			function searchOnKeypress(e){
				var keyCode = (window.event) ? e.keyCode : e.keyCode;

				if ( keyCode == 13 ) search();
			}
search;
		js::add( $js );


		$panel[] = "<div style='margin:5px 0' class='col-xs-12 col-md-2'>"
						. _T('search-tonality') . " : <select role='tonality' style='width:50px; height:28px'>" . join('', $tonalitys) ."</select>
					</div>";


		$panel[] = "<div style='margin-bottom:5px' class='col-xs-12 col-md-2'>
						<button onclick='search()'  class='btn btn-primary' type='button'>" . _T('search') . "</button>
					</div>";


		echo theme::block('', "<div class='clearfix'>" . join ('', $panel) . "</div>");

		$args = array(
			"keyword=" . urlencode($this->keyword),
			"tonality={$tonality}",
			"filter=" . implode( (array) $this->filter),
			"order=" . urlencode($this->order),
			"precedence=" . $this->precedence
		);

		echo theme::block('',
			$list->Close() .
			theme::page($this->poetrys->currPage, $this->poetrys->pageNum, WEB_ROOT . '/search/?' . join('&', $args), 10)
		);

		js::addLib(JS_PATH . '/jquery.highlight.js');
		$selectNoData = _T('selectNoData');

		$js = <<<search

		$(function(){
			var highlight = {$keyword};
			$('[role="highlighter"]').highlight(highlight.keyword);
		});

		function search(){
			var f = $('[role="filter"]'),
				filter = new Array();

			if ( f.find('input:not(:checked)').length != 0 ) {

				if ( f.find('input:checked').length == 0 ) {
					alert('{$selectNoData}');
					return;
				}

				f.find('input:checked').each(function(idx, itm){
					filter.push( $(itm).val() );
				});
			}

			$.Evt('poetry.search').publish( {
				'filter'	: filter.join(','),
				'keyword'	: $('[role="searcher"]').val(),
				'tonality'	: $('[role="tonality"]').val()
			} );
		}
search;

		js::add( $js );

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
	}

}