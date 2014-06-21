<?php

	$this->folders		= prog::get('folder')->getList();
	$this->tonalitys	= prog::get('poetry')->getTonality();

	$this->includeClass('sqllist.php');

	$this->filter		= args::get('filter', 'string');

	$param = $ext = array();

	if ( !$this->filter ) {

		if ( !$this->keyword ){
			$ext[] = 'search LIKE :keyword';
			$param[':keyword'] = "%{$this->keyword}%";

		} else {
			$keywords = explode(' ', $this->keyword);
			$search   = array();

			foreach ($keywords as $k => $v){

				if ( trim($v) == '' ) continue;

				$search[] = "search LIKE :keyword{$k}";
				$param[":keyword{$k}"] = "%{$v}%";
			}

			if ( count($search) == 0 ){
				$ext[] = 'search LIKE :keyword';
				$param[':keyword'] = "%{$this->keyword}%";
			} else {
				$ext[] = '(' . join(' AND ', $search) . ')';
			}
		}

	} else {

 		$this->filter = explode(',', $this->filter);
		$f = array();
		foreach ( $this->filter as $v ){
			$f[] = "{$v} LIKE :{$v}";
			$param[":{$v}"] = "%{$this->keyword}%";
		}

		$ext[] = '(' . join(' OR ', $f) . ')';
	}

	$tonality = args::get('tonality', 'int');

	if ( $tonality != 0 ) {
		$ext[]				= ' AND tonality=:tonality';
		$param[':tonality'] = $tonality;
	}

	$ext[]	= ' AND uploadComplete=:uploadComplete';
	$param[':uploadComplete'] = _UPLOAD_COMPLETE::complete;

	$this->order		= args::get('order',		'white_list', array('no', 'title', 'view', 'tonality', 'folderID', 'lastUpdate'));
	$this->precedence	= args::get('precedence',	'white_list', array( 'ASC', 'DESC'));
	$this->page			= args::get('page', 'int', 1);

	$this->poetrys = new SQLList(array(
		'query'		=> array(
			'sql'   => 'SELECT * FROM poetry WHERE ' . join(' ', $ext) . " ORDER BY {$this->order} {$this->precedence}",
			'param'	=> $param
		),
		'page' => $this->page,
		'size' => args::get('size', 'int', 30)
	));

	$data = array(
		'type' => 'html',
		'html' => array(
			'layout'	=> 'search',
			'template'	=> 'search'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);
	$this->doc = &$this->App->DOC->get('page.content');

	js::add(<<<js

		$(document).ready(function() {
			$('[data-toggle=offcanvas]').click(function() {
				$('.row-offcanvas').toggleClass('active');
			});

		});
js
);