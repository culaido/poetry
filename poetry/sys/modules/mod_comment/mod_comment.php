<?php
Class mod_comment Extends Module {

	public function getLink( $id='' ){
		return prog::get('comment')->getLink($id);
	}


	function ac_list($param){

		$limit = ($param['args']['limit']) ? $param['args']['limit'] : 10;

		$this->newest = db::select("SELECT * FROM comment WHERE uploadComplete=:uploadComplete ORDER BY lastPostTime DESC LIMIT {$limit}", array(
			':uploadComplete' => _UPLOAD_COMPLETE::complete
		));
	}

	function vw_list(){

		$list = array();

		foreach ($this->newest as $v){
			$list[] = "
				<div class='pull-right hint'>" . lib::timespan( $v['lastPostTime'] ) . "</div>
				<div class='title'>" . theme::a( lib::htmlChars($v['title']), $this->getLink( $v['id'] )) . "</div>";
		}

		echo '<div class="newestList">' . theme::block(
				_T('comment-lastEvent')  . '<span class="hint pull-right">' . theme::a('more', $this->getLink() ) . '</span>',
				theme::lists($list)
			) . '</div>';



	}

}