<?php

class mgr Extends Page {

    private $doc;

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }


	public function editable(){
		return Priv::check('mgr', 'editable');
	}


    function priv($param, $action) {

		if ( !$this->editable() ) {
			return;
		}

		if ( $action == '' )
			url::redir(WEB_ROOT . '/mgr/systemReport');

		return $action;
	}

    function exec($param, $action) {

		$this->mgr	= $this->get();
		$this->curr = $action;

		$layout = array(
			'mbox' => array( array( 'mgr' => array('menu' => '') ) ),
			'xbox' => array(
				array( 'mgr' => array('pageTitle' => '' ) ) ,
				array($this->mgr[$this->curr]['func'] => array( $this->mgr[$this->curr]['action'] => '') )
			)
		);

		$data = array(
			'type' => 'html',
			'html' => array(
				'title'		=> _T('System Mgr'),
				'layout'	=> $layout,
				'template'	=> 'mgr'
			),
			'content' => array()
		);

		$this->App->DOC->set('page', $data);
		$this->doc = &$this->App->DOC->get('page.content');
	}

	function vw_pageTitle(){}


	function vw_menu(){

		$item = array();
		$web_root	= WEB_ROOT;
		foreach ( $this->mgr as $v){

			$obj = prog::get($v['func']);

			$active = ( $v['method'] == $this->curr )
				? 'active' : '';

			$title = $obj->mgrTitle("{$web_root}/mgr/{$v['method']}");

			if ( !$title ) continue;
			$item[] = "<li class='{$active}'>" . $title . "</a></li>";
		}

		echo theme::block(_T('System Mgr'), '<ul class="memu nav nav-stacked">' . join('', $item) . '</ul>');
	}

	public function get(){

		$mgrs = db::select('SELECT * FROM mgr ORDER BY sn');
		$itms = array();

		while ($obj = $mgrs->fetch()) $itms[ $obj['method'] ] = $obj;

		return $itms;
	}

	public function mgrItemClac(){

		$cnt	= 0;

		$user	= prog::get('user')->getCnt();
		$cnt	+= $user['notAuth'];

		sys::setCfg('mgrItemCnt', $cnt);
	}
}