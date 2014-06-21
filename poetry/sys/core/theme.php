<?php

Class theme {

    private function prop($prop) {
        if (!is_array($prop)) return '';

		$tmp = array();
        foreach ($prop as $idx=>$val) $tmp[] = "{$idx}='{$val}'";
        return implode(' ', $tmp);
    }


    public static function header($title='', $ext='') {

		return "
			<div class='header clearfix'>
				<div class='title'>{$title}</div>
				<div class='ext clearfix'>{$ext}</div>
			</div>";
	}

    public static function page($curr, $total, $url, $pages=10, $prev_msg='Prev', $next_msg='Next', $go=TRUE) {
		if ($total===1 || !isset($total)) return;

		require_once CLASS_PATH . '/page.php';

        $page = new cpage($curr, $total, $url, $pages, $prev_msg, $next_msg, $go);
        return $page->create();
    }

	public static function icon($icon, $prop=array()){

		return "<span class='fa fa-{$icon}' " . self::prop($prop) . "></span>";
	}

	public static function grid($list){
		$item = array();

		foreach ($list as $val) {
            $item[] = (is_array($val))
				? "<li " . self::prop($val['prop']) . ">{$val['html']}</li>"
				: "<li class='item'>{$val}</li>";
        }

		return "<ul class='grid unstyled clearfix'>" . join('', $item) . "</ul>";
	}

	public static function hlists($list){

		$item = array();
        foreach ($list as $val) {

			$dt = "<dt>{$val[0]}</dt>";
			$dd = ( is_array($val[1]) )
				? "<dd " . self::prop($val['prop']) . ">{$val['html']}</dd>"
				: "<dd>{$val[1]}</dd>";

			$item[] = $dt . $dd;
        }

		return "<dl class='dl-horizontal clearfix'>" . join('', $item) . "</dl>";
	}

	public static function lists($list){
		$item = array();
        foreach ($list as $val) {
            $item[] = (is_array($val))
				? "<li " . self::prop($val['prop']) . ">{$val['html']}</li>"
				: "<li class='item'>{$val}</li>";
        }

		return "<ul class='list unstyled clearfix'>" . join('', $item) . "</ul>";

	}

	public static function block( $title, $content ){

		if ($title != '')
			$title = "<div class='title'>{$title}</div>";

		return "
			<section class='block clearfix'>
				{$title}
				<div class='content'>{$content}</div>
			</section>
		";
	}

	public static function a( $text, $href, $target='', $prop=array() ){

		$prop['href'] = $href;

		if ($target) $prop['target'] = $target;

		return "<a " . self::prop($prop) . ">{$text}</a>";
	}

	public function badges( $cnt, $type='' ){

		if ( $cnt > 100 ) $cnt ='99+';
		return "<span class='badge {$type}'>{$cnt}</span>";
	}


	public function breadcrumb( $data=array() ){

		$breadcrumb = array();
		
		if ( !is_array( $data ) ) $data = array();
		
		foreach ( $data as $v ){

			if ( !is_array( $v['class'] ) ) $v['class'] = array($v['class']);
			if ( $v['curr'] == true ) $v['class'][] = 'curr';
			
			if ( $v['link'] != '' ) $v['title'] = self::a( $v['title'], $v['link'] );

			$breadcrumb[] = "<span class='" . join(' ', $v['class']) . "'>" . $v['title'] . "</span>";
		}

		return join(' &gt; ', $breadcrumb);

	}

}