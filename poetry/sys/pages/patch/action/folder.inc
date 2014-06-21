<?php

	$orig = db::select('SELECT * FROM POETRY_ORIG ORDER BY SEQ_NBR ASC');

	db::query('TRUNCATE TABLE folder');
	
	while ( $obj = $orig->fetch() ){

		db::query('INSERT INTO folder SET 
			id=:id, 
			title=:title, 
			creator=:creator, 
			updater=:updater, 
			uploadComplete=:uploadComplete,
			lastUpdate=NOW()', array(
			
			'id'	=> $obj['SEQ_NBR'],
			'title' => $obj['ORIG_NM'],
			'creator' => profile::$id,
			'updater' => profile::$id,
			'uploadComplete' => _UPLOAD_COMPLETE::complete
		));
		
	}

	echo 'OK';