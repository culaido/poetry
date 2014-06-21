<?php

class patch Extends Page {

    private $doc;

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

	function priv($param, $action) {
		return $action;
	}

    function exec($param, $action) {

		$action_file = PAGE_PATH . "/patch/action/{$action}.inc";

		if (file_exists($action_file)) require($action_file);
	}
}