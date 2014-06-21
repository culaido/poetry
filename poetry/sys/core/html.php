<?php
require_once(CLASS_PATH . '/form.php');

Class Html {

	protected $page;
	protected $App;
	protected $bodyOnly;
	static $pageProg;

	public function __construct($page, $bodyOnly = FALSE)	{
	    $this->page = $page;
		$this->App = &prog::getApp();
		$this->bodyOnly = $bodyOnly;
	    self::$pageProg = &prog::get($page);
	}

	public function render($bodyOnly = FALSE) {

		$fmSubmit = $this->App->DOC->get('page.fmSubmit');
		$pageMode = $this->App->DOC->get('page.mode');

		if ($fmSubmit != 'yes') {

			if (!$this->bodyOnly)
				$this->head();

			$this->body();

			// proc if in a pageMode
			if ($pageMode == 'edit') {
				CForm::submit();
				CForm::render();
			}

			if (!$this->bodyOnly) {
				$this->addFooterScript();
				echo '</body></html>';
			}
		}
	}

	public function head()
	{
		$tplList = $this->App->DOC->get('page.tpl');
		foreach ($tplList as $tplName)
		{
			$tokens = explode('.', $tplName);
			array_pop($tokens);
			array_push($tokens, 'css');

			$path = implode('.', $tokens);

			css::addFile($path);
		}

    	echo <<<HDR
<!DOCTYPE html>
<html lang="zh-tw">
<head><meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />
<meta content="width=device-width,initial-scale=1" name="viewport">
HDR;

		$meta = $this->App->DOC->get('page.html.meta');

		// meta
		$meta = $this->App->DOC->get('page.html.meta');
		if (is_array($meta)) {

            foreach ($meta as $name => $value) {

				if (!is_array($value)) {
					$value = ($name == 'description') ? lib::formatMetaDescription($value) : strip_tags($value);
					echo "<meta name=\"{$name}\" content=\"". $value ."\" />\n";
					continue;
				}

				foreach ($value AS $prop=>$v) {
					echo "<meta {$name}=\"{$prop}\" content=\"". strip_tags($v[0]) ."\" />\n";
				}

                // TODO: *** filter is required
            }
        }
        $title = $this->App->DOC->get('page.html.title');
		if ( !$title) $title = self::siteName();

		echo '<title>' . lib::htmlChars($title) . '</title>' . "\n";

		// Link rel
		$link = $this->App->DOC->get('page.html.link');
		if (is_array($link)) {
			foreach ($link as $name => $value)
				echo "<link rel='alternate' type=\"{$name}\" title=\"" . lib::htmlChars($value['title']) . "\" href=\"". $value['href'] ."\" />\n";
		}

        // style
		$style = &$this->App->DOC->get('css.lib');

		if (is_array($style)) {
    		foreach ($style as $s) {

				if (substr($s, 0, 7)=='http://' || substr($s, 0, 8)=='https://' ) {
				//sys::print_var($s);
					$cssurl = $s;
				} else {
					$cssurl = ( (substr($s, 0, 1) != '/') ? "/{$s}" : $s );
				}


                echo '<link href="' . $cssurl . '" type="text/css" rel="stylesheet" />' . "\n";
    		}
    	}

		echo '<link rel="icon" href="favicon.ico" type="image/x-icon" />';

		// inline style
        $css = $this->App->DOC->get('css.style');
		if (is_array($css)) {
			echo '<style>';
				echo implode('', $css);
			echo '</style>';
		}

		$template = $this->App->DOC->get('page.html.template');
		echo "</head><body id='{$template}'>";
	}

	public function body()
	{
		$ver = lib::detectIEversion();

		if ( $ver > 0 && $ver < 9 ) {
			$alert = sprintf(_T('oldIEAlert'), $ver);
			echo "<div class='alert alert-error'><button type='type' class='close' data-dismiss='alert'>&times;</button>{$alert}</div>";
		}

		$page	= $this->App->DOC->get('page');
		$tpl	= ($page['html']['template'] == '') ? $page['html']['layout'] : $page['html']['template'];

		$t = prog::get('mod_template');

		$t->load_tpl("page.{$tpl}.tpl");
	}

    public function addFooterScript() {
        // js lib & src
        //  ***********************************
        $lib = $this->App->DOC->get('js.lib.header');  // array_unique, merge, ...

        if (is_array($lib)) {
            foreach ($lib as $s) {
				$jsurl = (substr($s, 0, 7)=='http://' || substr($s, 0, 8)=='https://' ) ? $s : $s;
                echo "<script src='{$jsurl}' type='text/javascript'></script>\n";
            }
        }

        $src = $this->App->DOC->get('js.src');
		if (is_array($src)) {
            echo '<script>'. implode("\n", $src) . '</script>';
        }

		$lib = $this->App->DOC->get('js.lib.footer');  // array_unique, merge, ...
        if (is_array($lib)) {
            foreach ($lib as $s) {
				$jsurl = (substr($s, 0, 7)=='http://' || substr($s, 0, 8)=='https://' ) ? $cdn_url.$s : $s;
				echo "<script src='{$url}' type='text/javascript'></script>\n";
            }
        }

        $lastSrc = $this->App->DOC->get('js.srcLast');
		if (is_array($lastSrc)) {
            echo '<script>'. implode("\n", $lastSrc) . '</script>';
        }

		if ( defined( 'GOOGLE_ANALYTICS' ) ){
			echo "
				<script>
				  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

				  ga('create', '" . GOOGLE_ANALYTICS . "', 'auto');
				  ga('send', 'pageview');

				</script>
			";
		}
    }



	public static function sysMenu() {

		if (!profile::$id) return;

		$func = array();

		$web_root = WEB_ROOT;

		$func[] = "<li><a href='{$web_root}/search/'>" . theme::icon('search') . " " . _T('search') . '</a></li>';

		$js =<<<search

			$.Evt('poetry.search').subscribe( function( args ){

				function urlencode(str) {
					str = (str + '').toString();
					return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
					replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
				}

				args = $.extend( { keyword:'', tonality:0 } , args );

				var param = new Array();
				for ( var k in args ) {
					param.push( k + '=' + urlencode( $.trim(args[k]) ) );
				}

				window.location.href = '{$web_root}/search/?' + param.join('&');
			});
search;

		js::add( $js );

		if ( defined( 'USER_VOICE' ) ) {

			$func[] = "<li><a href='javascript:showClassicWidget()'>" . theme::icon('bullhorn') . " " . _T('feedback') . "</a></li>";

			$user_voice = USER_VOICE;
			$js =<<<js

				(function(){var uv=document.createElement('script');uv.type='text/javascript';uv.async=true;uv.src='//widget.uservoice.com/YcJbNGgVMrCoPoqQOLrd1A.js';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(uv,s)})();

				UserVoice = window.UserVoice || [];

				function showClassicWidget() {
				  UserVoice.push(['showLightbox', 'classic_widget', {
					mode			: 'full',
					primary_color	: '#cc6d00',
					link_color		: '#007dbf',
					default_mode	: 'support',
					forum_id: {$user_voice}
				  }]);
				}
js;

			js::add( $js );
		}

		if ( Priv::check('mgr', 'editable') ) {

			$mgrItemCnt = sys::getCfg('mgrItemCnt', 0);

			$badges = '';

			if ( $mgrItemCnt != 0 )
				$badges = '<span title="' . _T('mgr-badges', array('%count%' => $mgrItemCnt)) . '">' . theme::badges( $mgrItemCnt, 'warning' ) . '</span>';

			$func[] = "<li><a href='{$web_root}/mgr/'>" . theme::icon('wrench') . " " . _T('manager') . ' ' . $badges . '</a></li>';
		}

		if ( Priv::check('comment', 'readable') ) {
			$func[] = "<li>" . theme::a(theme::icon('comments') . " " . _T('comment'), prog::get('comment')->getLink()) . "</li>";
		}

		echo '<ul class="nav navbar-nav navbar-right">
				' . join('', $func) . '
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown">'
						. theme::icon('user') . ' ' . lib::htmlChars(profile::$name) . ' <b class="caret"></b>
					</a>
					<ul class="dropdown-menu">
						<li><a href="' . $web_root . '/user/edit">'		. theme::icon('edit')	. " " . _T('user-edit') . '</a></li>
						<li><a href="' . prog::get('recent')->getLink(profile::$id) . '">' 	. theme::icon('list-ol') . " " . _T('read-lasting') . '</a></li>
						<li><a href="' . $web_root . '/playlist/my">' 	. theme::icon('caret-square-o-right')	. " " . _T('playlist-my') . '</a></li>
						<li><a href="javascript:logout()">' . theme::icon('sign-out')		. " " . _T('logout') . '</a></li>
					</ul>
				</li>
			</ul>';

		$url = lib::lockUrl(lib::htmlChars( WEB_ROOT . '/ajax/sys.pages.user/user.logout'), array('id' => profile::$id) );

		$js = <<<logout
			function logout(){
				$.post('{$url}', {}, function(obj){
					window.location.href = '{$web_root}';
				}, 'json');
			}
logout;

		js::add($js);
	}

	private function siteName(){
		return sys::getCfg('siteName', '');
	}

	public static function sysInfo() {

		echo theme::a(
			'<span class="fa fa-music"></span>&nbsp;' . lib::htmlChars( self::siteName() ),
			WEB_ROOT, '', array('class' => "navbar-brand"
		));
	}

	// INFO: Section utility functions
	public static function sys($pos) {

	}

	public static function banner() {
		self::$pageProg->vw_banner();
	}

	public static function footer() {
		self::$pageProg->vw_footer();
	}

	// INFO: Template rendering
	public static function renderRegion($box)
	{

		$app = &prog::getApp();
        $modules = $app->DOC->get("page.modules.{$box}");
        if (!is_array($modules))
		{
			echo '<div id="' . $box . '-rear" role="placeable"></div>';
			return;
		}

		$disabledModules = $app->DOC->get('page.disabledModules');

		//sys::print_var($modules);

        // echo "<b>$box</b> <br>";
		if (count($modules['inline'])==0)
		{
			if (count($modules['front'])==0)
				$box_pos = array('rear');
			else
				$box_pos = array('front', 'rear');
		}
		else
			$box_pos = array('front', 'inline', 'rear');

		foreach ($box_pos as $pos)
		{
			$boxname = $box . '-' . $pos;

			echo '<div id="' . $boxname . '" class="clearfix">';

			if (count($modules[$pos]) > 0) {
				foreach ($modules[$pos] as $item)
				{
					$name   = key($item);
					if (is_array($disabledModules) && in_array($name, $disabledModules))	continue;

					if (array_key_exists('action', $item[$name]))
					{
						$action = $item[$name]['action'];
						$func 	= key($action);
						$param	= $action[$func];
						$param['conf'] 	= $item[$name]['conf'];
					}
					else
					{
						$action = $item[$name];  // { "show": {"count":10} }
						$func   = key($action);
						$param 	= $action[$func];
					}

					$param['_box']		= $box;
					$param['_boxpos']	= $pos;

					$prog				= prog::get($name, $func, $param['blockId']);

					$prog->_blockId = ($param['blockId']=='') ? 0 : $param['blockId'];
					$prog->_action = $func;

					$classAttr = ( substr($name, 0, 4) == 'mod_' ) ? " class='{$name}'" : '';
					$idAttr = ($prog->_blockId==0) ? '' : " id='mod_{$prog->_blockId}'";

					echo "<div>";
						$prog->render($func, $param);
					echo "</div>";
				}
			}

			echo "</div>";
		}
	}
}