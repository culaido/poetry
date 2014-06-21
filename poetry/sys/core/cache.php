<?php
class cache
{

	public static function vol($key) {
		return join('/', str_split( substr($key, 0, 3), 1));
	}

	public static function getfile($path, $key) {
		$vol = self::vol($key);
		return SYSDATA_CACHE_PATH."/{$path}/{$vol}/{$key}.cache";
	}

    public static function get($path, $key, $debug=FALSE) {

        $file = self::getfile($path, $key);
        //if ($debug) sys::print_var($file, "cache::get");

		if (!file_exists($file)) return FALSE;

		return json_decode(file_get_contents($file), TRUE);
    }

	public static function overwrite($file, $data) {

		if ($file=='') return;
		sys::mkdir(ROOT_PATH .'/' . dirname($file));
		return file_put_contents($file, json_encode($data), LOCK_EX);
	}

    public static function set($path, $key, $data) {

        $file = self::getfile($path, $key);

		///		if (file_exists($file)) return FALSE;
		//rewrite when cache exist
		self::overwrite($file, $data);
		//sys::mkdir(ROOT_PATH .'/' . dirname($file));
		//return file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public static function clear($path, $key) {
		$file = self::getfile($path, $key);
        @ unlink($file);
    }

    public static function loadModule($page, $layout) {

		if (is_array($layout)) return $layout;
		if($layout=='') return;
		$file = "/{$page}/layout/{$layout}.modules.json";

		if (file_exists(PAGE_PATH.$file)) $file = PAGE_PATH.$file;

		if(!file_exists(SYSDATA_PATH . "/page"))			sys::mkdir(SYSDATA_PATH . "/page");
		if(!file_exists(SYSDATA_PATH . "/page/{$page}"))	sys::mkdir(SYSDATA_PATH . "/page/{$page}");

		$cache_file = SYSDATA_PATH . "/page/{$page}/{$layout}.modules.json";
		// TODO: remove below when go production
		@unlink($cache_file);

		if (!file_exists($cache_file))
		{
			file_put_contents($cache_file, file_get_contents($file), LOCK_EX);
		}

		return json_decode(file_get_contents($cache_file), TRUE);
    }

    public static function delModule($page, $layout) {
        @unlink(SYSDATA_PATH . "/page/{$page}/{$layout}.modules.json");
    }
}
