<?php
require_once(dirname(__FILE__) . '/module.php');

class Page extends Module {

	public function __construct($file, $mode, $app)	{
		parent::__construct($file, $mode, $app);

	    $this->App->DOC->set('app.page', array('dir' => $this->dir));
		self::$_pageMode = $this->App->DOC->get('page.mode');
	}

	public function vw_breadcrumb() {
		echo '<div class="breadcrumb">' . theme::block('', _T('index') . ': ' . theme::breadcrumb( $this->breadcrumb ) ) . '</div>';
	}

	public function vw_banner() {}

	public function vw_footer() {
	
		$timezone = cookie::get('timezone', 'string');

		if ($timezone === '') {
			$domain = COOKIE_DOMAIN;
			js::addlib(JS_PATH . '/jquery.cookie.js');
			js::addLast(<<<JS
var d = new Date();
var n = (d.getTimezoneOffset() * -1) / 60;
	$.cookie("timezone", n, { path: '/', expires: 7, domain: '{$domain}' });
JS
);
		}
	}

	public function setTpl($tplType, $tplFileName, $tplPath = '') {
		$t = prog::get('mod_template');
		$t->set_tpl($tplType, $tplFileName, $tplPath);
	}
}