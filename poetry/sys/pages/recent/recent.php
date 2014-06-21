<?php

Class recent extends Page {

    function __construct($file, $mode, $app) { parent::__construct($file, $mode, $app); }

	function exec($param, $action){

		$userId = (int) $param[0];
		if (!$userId) $userId = profile::$id;

		$recent = db::select('SELECT * FROM recent WHERE userId=:userId ORDER BY lastUpdate DESC LIMIT 100', array(
			':userId' => $userId
		));

		$this->breadcrumb[]= array('title' => _T('My Read Latest'), 'curr' => true);

		$this->recent = array();

		while ( $obj = $recent->fetch() ) {
			$this->recent[ $obj['poetryId'] ] = $obj;
		}

		$this->lists = prog::get('poetry')->get( array_keys( $this->recent ) );

		$data = array(
			'type' => 'html',
			'html' => array(
				'title'		=> _T('siteName'),
				'layout'	=> array(
					'xbox' => array(
						array('recent'	=> array('breadcrumb'	=> array())),
						array('recent'	=> array('list'			=> array()))
					)
				),
				'template'	=> 'recent'
			),
			'content' => array()
		);

		$this->App->DOC->set('page', $data);
	}

	public function vw_list(){

		$this->includeClass('list.php');

		$list = new CList('t1');

		$hdr = array(
			'no'			=> array('title' => _T('poetry-no'),		'width' => '60px',  'align' => 'center',	'cAlign' =>	'center'),
			'title'			=> array('title' => _T('poetry-title'),		'width' => '180px',	'align' => 'left',		'cAlign' =>	'left'),
			'search'		=> array('title' => _T('poetry-search'),	'width' => '',		'align' => 'left',		'cAlign' =>	'left'),
			'view'			=> array('title' => _T('poetry-view'),		'width' => '60px',	'align' => 'center',	'cAlign' =>	'center'),
			'lastUpdate'	=> array('title' => _T('read-time'),		'width' => '120px',	'align' => 'center',	'cAlign' =>	'center')
		);

	    $list->setHeader($hdr);
		$align = array();
		foreach ($hdr as $k=>$v){
			$align[$k] = $v['cAlign'];
		}

		$list->setDataAlign($align);

		$url = array();

		foreach ( $this->recent as $id => $r ){

			$v = $this->lists[$id];

			$list->setData(array(
				'no'		=> $v['no'],
				'title'		=> theme::a( lib::htmlChars($v['title']), WEB_ROOT . '/poetry/' . $v['id']),
				'search'	=> lib::htmlChars($v['search']),
				'view'		=> $v['view'],
				'lastUpdate'=> lib::dateFormat($r['lastUpdate'])
			));
		}

		echo theme::block(_T('My Read Latest'), $list->Close() );

	}


	public function getLink($userId){
		return WEB_ROOT . "/recent/{$userId}";
	}

	public function updateLog($userId, $poetryId){

		db::query('INSERT INTO recent SET userId=:userId, poetryId=:poetryId, lastUpdate=NOW() ON DUPLICATE KEY UPDATE lastUpdate=NOW()', array(
			':userId'	=> $userId,
			':poetryId'	=> $poetryId
		));
	}

	function hk_onPoetryRemove( $param ){
		ignore_user_abort(TRUE);
		db::query('DELETE FROM recent WHERE poetryId=:poetryId', array(':poetryId' => $param['id']));
	}
}

