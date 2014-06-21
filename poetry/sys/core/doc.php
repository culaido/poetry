<?php
class Doc {
	public $_data = array();

	function __construct($data = null)
	{
		if ($data != null) $this->_data = data;
	}

	function &get($path)
	{
		// $path: content.media.type.copyright
		if($path === NULL) return $this->_data;

		$ref = &$this->_data;
		//$ary = split("\.", $path);
		$ary = preg_split('/\./', $path);

		foreach($ary as $v) {
		    if (is_string($ref)) return FALSE;  // used for get & set, some tricky as type is string
			$ref = &$ref[$v];  // created if not exists
		}

		return $ref;
	}


	function &set($path, $data, $overwrite = FALSE)
	{
		if ($overwrite || !is_array($data) ) {
			$node = &$this->get($path);
			$node = $data;
		} else {
			$node = &$this->merge($path, $data);
		}
	
		return $node;
	}


	function del($path)
	{
		$ary = explode('.', $path);
		if (count($ary) == 1) {
		    unset($this->_data[$path]);
		    return;
		}

		$item = array_pop($ary);
		$path = implode('.', $ary);
		$node = &$this->get($path);

		unset($node[$item]);
	}

	function &merge($path, $data, $unique = FALSE)
	{
	   if (!is_array($data)) $data = array($data);

	    $node = &$this->get($path);
	    if (is_array($node))
        {
	        $node = array_merge($node, $data);
			if ($unique)
				return array_unique($node);
			else
				return $node;
	    }

        $node = $data;
        return $node;
	}
}


class g {
    public static $VAR;

	public static function _init() {
		self::$VAR = new Doc;
	}
}