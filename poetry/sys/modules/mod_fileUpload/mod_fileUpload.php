<?php
require_once MODULE_PATH . '/mod_fileUpload/UploadHandler.php';

Class mod_fileUpload Extends Module {

	function getList( $pageId ){

		$items = db::select('SELECT * FROM file_upload WHERE pageId=:pageId', array(':pageId' => $pageId));
		$item  = array();

		while ($obj = $items->fetch()){
			$item[] = $obj;
		}

		return $item;
	}

	function get( $id ){
		return db::fetch('SELECT * FROM file_upload WHERE id=:id', array(':id' => $id));
	}

	function remove( $id ){

		$item = $this->get( $id );

		if ( !$item ) return false;

		if ( $item['path'] == '' ) return;

		$path = ROOT_PATH . $item['path'];

		if ( ROOT_PATH == $path ) return;

		if ( file_exists($path) ) {
			db::query('DELETE FROM file_upload WHERE id=:id', array(':id' => $id));
			return unlink($path);
		}
	}

	function ax_upload(){

		$pageId = args::get('pageId', 'nothing');
		$name	= args::get('name', 'nothing');

		$options = array(
			'user_dirs'			=> false,
			'random_file_name'	=> true,
			'param_name'		=> $name,
			'pageId'			=> $pageId,
			'upload_dir'		=> ROOT_PATH . '/' . SYSDATA_PATH .  "/attach/{$pageId}/",
			'upload_url'		=> ROOT_PATH . '/' . SYSDATA_PATH .  "/attach/{$pageId}/"
		);

		$upload_handler = new CustomUploadHandler( $options );
	}



	function removeDir( $path ){

		if ( $path == '' ) return false;

		$path = ROOT_PATH . $path;

		$prefix = substr( $path, 0, strlen(ROOT_PATH . '/' . SYSDATA_PATH . '/attach'));

		if ( $prefix != ROOT_PATH . '/' . SYSDATA_PATH . '/attach') return false;

		if ( !is_dir($path) ) return false;

		log::save('file', 'remove', $path);

		$this->rrmdir($path);
	}

	function rrmdir($target) {

		if ( is_dir($target) ){
			$files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

			foreach( $files as $file ) $this->rrmdir( $file );

			if ( file_exists($target) ) rmdir( $target );

		} else if(is_file($target)) {
			unlink( $target );
		}
	}








	function hk_onMusicRemove( $param ){

		ignore_user_abort(TRUE);

		$id = $param['id'];
		$musics = $this->getList( 'mod_music.' . $id );


		$item = $musics[0];
		if (!$item) return;

		$path = dirname( $item['path'] );
		$this->removeDir($path);

		db::query('DELETE FROM file_upload WHERE pageId=:pageId', array(':pageId' => $item['pageId'] ));

	}

	function hk_onPoetryRemove( $param ){

		ignore_user_abort(TRUE);

		$id = $param['id'];
		$musics = $this->getList( 'poetry.' . $id );

		$item = $musics[0];
		if (!$item) return;

		$path = dirname( $item['path'] );
		$this->removeDir($path);

		db::query('DELETE FROM file_upload WHERE pageId=:pageId', array(':pageId' => $item['pageId'] ));

	}
}




class CustomUploadHandler extends UploadHandler {

	protected function initialize( ) {
		parent::initialize();
	}

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {

		$file = parent::handle_file_upload(
			$uploaded_file, $name, $size, $type, $error, $index, $content_range
		);

        if ($file->error) return $file->error;

		db::query('INSERT INTO file_upload SET
			pageId	= :pageId,
			srcName	= :srcName,
			name	= :name,
			path	= :path,
			createTime = NOW(),
			size	= :size,
			userID	= :userID,
			mimeType= :mimeType', array(
				':pageId'	=> $this->options['pageId'],
				':srcName'	=> $this->options['srcName'],
				':path'		=> $this->options['path'],
				':name'		=> $file->name,
				':size'		=> $file->size,
				':userID'	=> profile::$id,
				':mimeType'	=> $file->type
		));

		$file->path		= $this->options['path'];
		$file->srcName	= $this->options['srcName'];
		$file->pageId	= $this->options['pageId'];
		$file->id = db::lastInsertId();


        return $file;
    }

	protected function trim_file_name($file_path, $name, $size, $type, $error, $index, $content_range) {

		$this->options['srcName'] = $name;

		$ext = lib::ext( $this->options['srcName'] );

		$name = uniqid() . ".{$ext}";
		$this->options['path'] = '/' . SYSDATA_PATH .  "/attach/{$this->options['pageId']}/{$name}";
		
        return $name;
    }

    public function delete($print_response = true) {
        $response = parent::delete(false);
        foreach ($response as $name => $deleted) {
        	if ($deleted) {
	        	$sql = 'DELETE FROM `'
	        		.$this->options['db_table'].'` WHERE `name`=?';
	        	$query = $this->db->prepare($sql);
	 	        $query->bind_param('s', $name);
		        $query->execute();
        	}
        }
        return $this->generate_response($response, $print_response);
    }
	/*
	protected function generate_response($content, $print_response = true) {
		$data = array_shift($content[ $this->options['param_name'] ]);
		lib::filelog( $data );
		return array('status'=>true, 'ok', '',  array( 'file' => $data ));
	}*/

}
