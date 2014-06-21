<?php

	$this->breadcrumb = array();
	$this->breadcrumb[] = array('title' => _T('comment'), 'link' => $this->getLink());

	if ( $this->_id == 0 ) {

		$this->doc = array('title' => _T('comment-list'));

		$this->includeClass('sqllist.php');

		$this->order		= args::get('order', 'white_list', array('id', 'title', 'lastComment', 'lastPostTime'));
		$this->precedence	= args::get('precedence', 'white_list', array('DESC', 'ASC'));
		$this->page			= args::get('page', 'int', 1);

		$comments = new SQLList(array(
			'query'		=> array(
				'sql'   => "SELECT * FROM comment WHERE uploadComplete=:uploadComplete ORDER BY {$this->order} {$this->precedence}",
				'param'	=> array(
					':uploadComplete' => _UPLOAD_COMPLETE::complete
				)
			),
			'page' => $this->page,
			'size' => args::get('size', 'int', 10)
		));

		$this->currPage	= $comments->currPage;
		$this->pageNum	= $comments->pageNum;

		$this->comments = $lastPoster = array();
		while ( $obj = $comments->fetch() ){
			$this->comments[] = $obj;
			$lastPoster[] = $obj['lastPoster'];
		}

		$this->lastPoster = prog::get('user')->get( $lastPoster );

		$data = array(
			'type' => 'html',
			'html' => array(
				'layout' => array(

					'mbox' => array(
						array('mod_poetry'	=> array('hotest'	=> array('limit' => 10))),
						array('mod_poetry'	=> array('newest'	=> array('limit' => 10)))
					),

					'xbox' => array(
						array('comment'	=> array('breadcrumb'	=> array())),

						array('comment'	=> array('title'	=> array())),
						array('comment'	=> array('list'		=> array()))
					)
				),
				'template'	=> 'comment'
			),
			'content' => array()
		);

	} else {

		$config = db::fetch('SELECT * FROM comment_config WHERE name=?', array('shortname')); 
		
		$this->shortname = $config['value'];
		
		$this->doc = array_shift( $this->get( $this->_id ) );
		$this->breadcrumb[] = array('title' => $this->doc['title'], 'curr' => true);

		$this->creator = array_shift( prog::get('user')->get( $this->doc['creator'] ) );

		$data = array(
			'type' => 'html',
			'html' => array(
				'layout' => array(

					'mbox' => array(
						array('mod_comment'	=> array('list'	=> array('limit' => 20)))
					),

					'xbox' => array(
						array('comment'	=> array('breadcrumb'	=> array())),
						array('comment'	=> array('show'			=> array())),
						array('comment'	=> array('comment'		=> array()))
					)
				),
				'template'	=> 'comment'
			),
			'content' => array()
		);
	}


	$this->App->DOC->set('page', $data);
	js::add(<<<js

		$(document).ready(function() {
			$('[data-toggle=offcanvas]').click(function() {
				$('.row-offcanvas').toggleClass('active');
			});

		});
js
);

