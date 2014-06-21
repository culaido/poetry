<?php

	$this->doc = array_shift( $this->get( $this->_id ) );

	$this->tonality = $this->getTonality( $this->doc['tonality'] );

	if ( !$this->doc ) {
		echo theme::block('', _T('poetry-notExist'));
		return;
	}

	$this->folder	= array_shift( prog::get('folder')->get( $this->doc['folderID'] ) );

	$this->creator	= array_shift( prog::get('user')->get( $this->doc['creator'] ) );
	$this->updater	= array_shift( prog::get('user')->get( $this->doc['updater'] ) );

	$this->breadcrumb = array();

	$this->breadcrumb[] = ( !$this->folder )
		? array('title' => theme::a( lib::htmlChars(_T('folder-root')), WEB_ROOT . '/folder/0' ))
		: array('title' => theme::a( lib::htmlChars($this->folder['title']), WEB_ROOT . '/folder/' . $this->doc['folderID'] ));


	$this->breadcrumb[]= array('title' => lib::htmlChars($this->doc['title']), 'curr' => true);

	$data = array(
		'type' => 'html',
		'html' => array(
			'title'		=> $this->doc['title'],
			'layout'	=> array(
				'mbox'	=> array(
					array('mod_poetry'	=> array('hotest'	=> array('limit' => 10))),
					array('mod_poetry'	=> array('newest'	=> array('limit' => 10))),
					array('mod_folder'	=> array('list'		=> array()))

				),

				'xbox' => array(
					array('poetry'		=> array('breadcrumb'	=> array())),
					array('poetry'		=> array('title'		=> array()))

				),
				'xboxL' => array(
					array('poetry'		=> array('meta'		=> array())),
					array('mod_music'	=> array('show'		=> array())),
					array('mod_tag'		=> array('show'		=> array())),
					array('poetry'		=> array('lyric'	=> array())),
					array('poetry'		=> array('embed'	=> array())),
					array('poetry'		=> array('note'		=> array()))
				),
				'xboxR' => array(
					array('mod_notation'	=> array('show'		=> array('type' => 1))),
					array('mod_notation'	=> array('show'		=> array('type' => 2))),
					array('mod_playlist'	=> array('show'		=> array()))
				)
			),
			'template'	=> 'poetry'
		),
		'content' => array()
	);

	$this->App->DOC->set('page', $data);

	if ($_SESSION['view']['poetry'][$this->_id] != 1) {
		$_SESSION['view']['poetry'][$this->_id] = 1;
		db::query('UPDATE LOW_PRIORITY poetry SET view=view+1 WHERE id=?', array($this->_id));
		$this->doc['view'] += 1;
	}

	prog::get('recent')->updateLog(profile::$id, $this->_id);

	js::add(<<<js

		$(document).ready(function() {
			$('[data-toggle=offcanvas]').click(function() {
				$('.row-offcanvas').toggleClass('active');
			});
		});
js
);