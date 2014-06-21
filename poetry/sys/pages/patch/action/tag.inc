<?php

	$poetry = db::select('SELECT * FROM poetry WHERE uploadComplete=:uploadComplete', array(
		':uploadComplete' => 1
	));


	while ( $obj = $poetry->fetch() ){

		$tags = db::select('SELECT * FROM tag_item WHERE pageId=:pageId ORDER BY tagId ASC', array(
			':pageId' => "poetry.{$obj['id']}"
		));

		$tag = array();

		while ( $t = $tags->fetch() ){
			$tag[] = $t['tagId'];
		}

		db::query('UPDATE poetry SET tags=:tags WHERE id=:id', array(
			':tags' => ',' . join(',', $tag) . ',',
			':id'	=> $obj['id']
		));
	}


	echo 'done';