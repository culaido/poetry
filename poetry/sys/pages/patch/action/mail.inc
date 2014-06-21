<?php


	$users = db::select('SELECT email FROM user');
	$user = array();
	
	while ( $obj = $users->fetch() ){
		$user[] = $obj['email'];
	}
	
	echo join(', ', $user);