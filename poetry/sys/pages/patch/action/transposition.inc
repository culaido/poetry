<?php


	$poetryObj = prog::get('poetry');

	$objs = db::select('SELECT * FROM poetry');
	
	while ( $obj = $objs->fetch() ){
		$poetryObj->saveSearch( $obj['id'] );
	}
	
	echo 'ok';