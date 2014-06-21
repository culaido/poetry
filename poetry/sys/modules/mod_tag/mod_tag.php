<?php

Class mod_tag Extends Module {

	public function mgrTitle(){


	}

	public function formSave(){

		$pageId	= $this->getArgs('pageId', 'nothing');
		$tags	= $this->getArgs('tag', 'nothing');

		$selected = array();
		$o = $this->get( $pageId );

		if ( !$tags ) {

			db::query('DELETE FROM tag_item WHERE pageId=:pageId', array(':pageId'	=> $pageId));

			foreach ( $o as $v ) db::query('UPDATE tag AS t SET cnt=(SELECT COUNT(*) FROM tag_item WHERE tagId=t.id) WHERE id=?', array( $v ));

			return;
		}

		$tags	= explode( ',', $tags );

		foreach ( $o as $v ) $selected[ $v['tagId'] ] = $v['tagId'];

		foreach ( $tags as $v ){

			if ( array_key_exists( $v, $selected ) ) {
				unset( $selected[$v] );
				continue;
			}

			db::query('INSERT INTO tag_item SET pageId=:pageId, tagId=:tagId, lastUpdate=NOW()', array(
				':pageId'	=> $pageId,
				':tagId'	=> $v
			));

			unset( $selected[$v] );
		}

		foreach ( $selected as $v ) {

			db::query('DELETE FROM tag_item WHERE pageId=:pageId AND tagId=:tagId', array(
				':pageId'	=> $pageId,
				':tagId'	=> $v
			));

			db::query('UPDATE tag AS t SET cnt=(SELECT COUNT(*) FROM tag_item WHERE tagId=t.id) WHERE id=?', array( $v ));
		}

		$this->updatePoetryTag( $pageId );

		foreach ( $tags as $v ) db::query('UPDATE tag AS t SET cnt=(SELECT COUNT(*) FROM tag_item WHERE tagId=t.id) WHERE id=?', array( $v ));
	}


	public function updatePoetryTag( $pageId ){

		$tags = $this->get( $pageId );

		$tag = array();
		foreach ($tags as $v) {
			$tag[] = $v['tagId'];
		}

		list($page, $id) = lib::parsePageId($pageId);

		prog::get('poetry')->save($id, array(
			'tags' => ',' . join(',', $tag) . ','
		));

	}

	public function ac_show(){

		$this->pageId	= $this->getPageId();
		$this->tags		= $this->get( $this->pageId );

		if ( count($this->tags) == 0 ) return;

		$this->tag		= $this->getAll();
	}

	public function vw_show(){

		if ( count($this->tags) == 0 ) return;

		$data = array();

		foreach ( $this->tags as $v )
			$data[] = theme::a( lib::htmlChars( $this->tag[$v['tagId']]['name'] ), $this->getlink( $v['tagId'] ) ) . "</span>";

		echo theme::block('', theme::icon('tags') . ' <span class="hint">' . _T('tags') . ':</span> ' . join(', ', $data) );
	}


	public function ac_cloud(){
		$this->cloud = $this->getAll();
	}

	private function array_sort($array, $on, $order=SORT_ASC)
	{
		$new_array = array();
		$sortable_array = array();

		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}

			switch ($order) {
				case SORT_ASC:
					asort($sortable_array);
				break;
				case SORT_DESC:
					arsort($sortable_array);
				break;
			}

			foreach ($sortable_array as $k => $v) {
				$new_array[$k] = $array[$k];
			}
		}

		return $new_array;
	}


	public function vw_cloud(){

		$tags = array();

		$this->cloud = $this->array_sort( $this->cloud, 'cnt', SORT_DESC );

		foreach ( $this->cloud as $v ){

			if ( $v['cnt'] == 0 ) continue;

			$cnt = ' <span class="hint">(' . $v['cnt'] . ')</span>';
			$tags[] = theme::a( '<span style="white-space:nowrap">' . lib::htmlChars( $v['name'] ) . $cnt . '</span>', $this->getlink( $v['id'] ));
		}

		echo theme::block(_T('tags'), join( ', ', $tags ));
	}

	public function getlink( $id=array() ) {
		if ( !is_array( $id ) ) $id = array( $id );
		
		return WEB_ROOT . '/tag/?tags=' . join('-', $id);

	}





	public function get( $pageId ){
		$tag = array();
		$tags = db::select('SELECT * FROM tag_item WHERE pageId=?', array($pageId));
		while ( $obj = $tags->fetch() )
			$tag[] = $obj;

		return $tag;
	}

	public function getCnt(){
		return count($this->getAll());
	}

	public function getAll(){
		return prog::get('tag')->getAll();
	}



	public function ac_edit(){

		$this->pageId = $this->getPageId();

		$this->tags = $this->getAll();
		$this->tag 	= $this->get( $this->pageId );

		$selected = array();
		foreach ( $this->tag as $v ) $selected[] = $v['tagId'];

		$this->form['pageId']	= CForm::hidden($this, 'pageId', $this->pageId);
		$this->form['tag']		= CForm::hidden($this, 'tag', join(',', $selected), array());

		js::addLib(JS_PATH . '/select2.min.js');
		js::addLib(JS_PATH . '/select2_locale_zh-TW.js');

		css::addFile(CSS_PATH . '/select2.css');

		css::add("
			.select2-drop li {float:left; margin:10px;}
		");
	}


	public function vw_edit(){

		$selected = array();
		foreach ( $this->tag as $v ) $selected[ $v['tagId'] ] = 'selected';

		$opt = array();
		foreach ( $this->tags as $v ) {
			$opt[] = "<option value='{$v['id']}' {$selected[ $v['id'] ]}>" . lib::htmlChars($v['name']) . "</option>";
		}

		$tag = CForm::getId($this, 'tag');

		$js = <<<js

			$(function(){
				var tag = '{$tag}';
				$('#s1').select2({
					width: "100%",
					allowClear: true
				}).on('change', function(e){

					var v = $(e.target).val();

					if ( !v ) {
						$('#' + tag).val('');
						return;
					}

					$('#' + tag).val( v.join(',') );
				});
			});
js;

		js::add($js);

		echo theme::block(_T('tags'), '<select id="s1" multiple>' . join($opt) . '</select>');
	}

}