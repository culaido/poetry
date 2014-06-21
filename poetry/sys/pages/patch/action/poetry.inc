<?php

	$folder = db::select('SELECT * FROM folder');

	$map = array();
	while ( $obj = $folder->fetch() ){
		$map[ $obj['title'] ] = $obj['id'];
	}

	$poetry_data = db::select('SELECT * FROM POETRY_DATA');

	db::query('TRUNCATE TABLE poetry');
	db::query('TRUNCATE TABLE music');
	db::query('TRUNCATE TABLE notation');

	$poetryObj = prog::get('poetry');

	while ( $obj = $poetry_data->fetch() ){

		$fid = ( $map[ $obj['SONG_ORIG'] ] == '' ) ? 0 : $map[ $obj['SONG_ORIG'] ];
		$ret = $poetryObj->create( array('folderID' => 0 ) );

		$obj['SONG_LANG'] = ( $obj['SONG_LANG'] == 1 ) ? 0 : 1;

		$data = array(
			'no'		=> $obj['SEQ_NBR'],
			'folderID'	=> $fid,
			'title'		=> html_entity_decode( $obj['SONG_NM'], ENT_QUOTES, "utf-8"),
			'lyric'		=> html_entity_decode( $obj['SONG_CHAR'], ENT_QUOTES, "utf-8"),
			'tonality'	=> $obj['SONG_KEY'],
			'transposition'	=> '',
			'lang'		=> $obj['SONG_LANG'],
		);

		$ret = $poetryObj->save( $ret['data']['id'], $data );
	}


	$folderObj = prog::get('folder');

	$folder = db::select('SELECT * FROM folder');

	while ( $obj = $folder->fetch() ){
		$folderObj->poetryCount( $obj['id'] );
	}


	echo 'OK';