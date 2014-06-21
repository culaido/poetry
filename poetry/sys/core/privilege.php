<?php
class Priv {

	static $priv = array();
	static function check( $page, $action, $role=0 ){

		if ( $role == 0 ) $role = profile::$role;

		if ( self::$priv[ $page ][ $action ][ $role ] ) {

			return self::$priv[ $page ][ $action ][ $role ];
		}

		$obj = db::fetch('SELECT * FROM priv WHERE page=:page AND action=:action AND role=:role', array(
			':page'		=> $page,
			':action'	=> $action,
			':role'		=> $role
		));

		self::$priv[ $page ][ $action ][ $role ] = ( $obj['priv'] == 1 ) ? true : false;

		return self::$priv[ $page ][ $action ][ $role ];
	}

}

class Role {

	static $role = array();
	static function get(){

		if ( count( self::$role ) != 0 ) return self::$role;

		$roles = db::select('SELECT * FROM role ORDER BY sn ASC');
		$role  = array();
		while ( $obj = $roles->fetch() ){
			$role[ $obj['id'] ] = $obj;
		}

		self::$role = $role;

		return self::$role;
	}

	static function getType(){

		$r = self::get();
		
		$type = array();
		foreach ( $r as $v ) $type[] = $v['id'];
		
		return $type;
	}
}