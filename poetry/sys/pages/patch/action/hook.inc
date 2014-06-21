<?php

	db::query('TRUNCATE TABLE hook');

	$hook = array();

	$hook[] = array('folder.before.remove', 'poetry',			'onFolderRemove');

	$hook[] = array('poetry.before.remove', 'mod_music',		'onPoetryRemove');
	$hook[] = array('poetry.before.remove', 'mod_fileUpload',	'onPoetryRemove');
	$hook[] = array('poetry.before.remove', 'mod_notation',		'onPoetryRemove');

	$hook[] = array('poetry.after.save',	'folder',			'onPoetrySave');
	$hook[] = array('poetry.after.remove',	'folder',			'onPoetryRemove');

	$hook[] = array('music.before.remove',	'mod_fileUpload',	'onMusicRemove');


	foreach( $hook as $v ){

		db::query('INSERT INTO hook SET event=:event, module=:module, func=:func', array(
			':event'	=> $v[0],
			':module'	=> $v[1],
			':func'		=> $v[2]
		));
	}