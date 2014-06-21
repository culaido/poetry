<?php
Class mod_fileUpload_helper {
	const SECRET_KEY = 17;

	static function checkKey($id, $key){
		$oldKey = self::getKey($id);
	//	echo $oldKey  . ' --- ' . $key;

		return ($oldKey == $key) ? TRUE : FALSE;
	}

	static function getKey($id){
		return md5($id . ($id * self::SECRET_KEY));
	}

	static function load($pageId, $id=''){
        $items = db::select('SELECT * FROM mod_attach_item WHERE pageId=:pageId ORDER BY id ASC', array(
            ':pageId'=> $pageId
        ));

        $attach = array();
        while ($obj = $items->fetch()) {
			$key = self::getKey($obj['id']);

            $attach[] = array(
                'id'        => $obj['id'],
                'path'      => $obj['path'],
                'srcName'   => $obj['srcName'],
                'size'      => $obj['size'],
				'key'		=> $key
            );
        }

        return $attach;
	}


	static function quotaLimit($pageId){

		$owner = lib::getOwner($pageId);

		switch ($owner['type']) {
			case _OWNER_TYPE::user:
				$userObj = prog::get('user');
				$data = array_shift($userObj->getInfo($owner['userID']));
			break;

		}

		return array( 'quotaUsed' => $data['quotaUsed'], 'quotaLimit' => $data['quotaLimit'] );
	}

	static function quotaDeduct($pageId, $size){
		self::quotaCompute( $pageId, $size );
	}

	static function quotaReturn($pageId, $size){
		self::quotaCompute( $pageId, (-1 * $size) );
	}

	static function quotaCompute($pageId, $size){

		if ($size == 0) return;

		$owner = lib::getOwner($pageId);
//print_r($owner);

		// use + or - in sql to compute quota, avoid critical section problem
		switch ($owner['type']) {
			case _OWNER_TYPE::user:
				db::query("UPDATE user SET quotaUsed=quotaUsed+{$size} WHERE id=?", array($owner['userID']));
			break;

		}


	}
}