<?php
require_once 'doc.php';
Class App {

	// capital for static member
	public $DOC;
	public $page;
	public $pObj; // page object
	private $mode;

	private $_curModule;

	function __construct($args = array()) {

		$this->DOC = new Doc;
		$this->DOC->set('app.args', $args);

		prog::setApp($this);

		return $this;
	}


	public function loadPage() {

		// load all necessary script & css files
		self::loadPageScript();

		$urlinfo = parse_url($_SERVER['REQUEST_URI']);

		$this->DOC->set('page.urlinfo', $urlinfo);

		$_lock		= args::get('_lock', 'string');
		$ajaxAuth	= args::get('ajaxAuth', 'string');

		if ($ajaxAuth!='') {

			$ajaxUrl = $urlinfo['path'];

			$_vals = array();

			if ($_lock!='') {
				$_fields = explode(',', $_lock);
				foreach ($_fields as $f) {
					$_vals[$f] = args::get($f, 'nothing');
				}
			}

			if (in_array('_pageMode', explode(',', $_lock)))
				$_pageMode = args::get('_pageMode', 'string');


			if ( lib::ajaxAuth( $ajaxUrl, $_vals ) != $ajaxAuth ) {

				if (__DEBUG__) {
					print_r( array( 'ajaxAuth' => $ajaxAuth,
									'_url' => $ajaxUrl,
									'_lock' => $_lock,
									'fields' => $_fields,
									'vals' => $_vals,
									'server-computing-auth' => lib::ajaxAuth( $ajaxUrl, $_vals )));

					echo "{\"err\": \"wrong ajaxAuth! (app.php)\"}";

				}
				else
				{
					echo json_encode(array( 'ret' => array( 'status' => 'false', 'msg'	=> _T('logoutRefreshPS'))));
				}

				exit();
			}
		}



		if ($_pageMode != '') {
			$this->DOC->set('page.mode', $_pageMode);

			/////////////////
			// if form submitted
			$fmSubmit = args::get('_fmSubmit', 'string');
			if ($fmSubmit == 'yes') {
				$this->DOC->set('page.fmSubmit', $fmSubmit);

			}
		}


		$args       = $this->DOC->get('app.args');
	    $this->page = $page = $args['page'];

		$csspath[] = PAGE_PATH . "/{$this->page}/style.css";

		foreach ($csspath as $path) {
			if (file_exists($path)) {
				css::addFile($path);
				break;
			}
		}

		$action = basename($args['action']);

        $this->pObj = &prog::get($page, $action, 0, TRUE);
		$pageId = $this->pObj->getPageId();

		layoutMgr::setPageId($pageId);

		$this->pObj->_blockId = '0';
		$this->pObj->_action = '';

		$action = ($this->pObj->priv($args['param'], $action));

		$this->pObj->exec($args['param'], $action);

		$tpl = prog::get('mod_template');
		$tpl->prepare();

		$tpl->prepareTpl($pageId);

	}

	public function actSave() {

		// do page save
		$this->pObj->formSave();
		$mods = prog::getModules();

		// do modules save();
		foreach ($mods as $modlist) {
			foreach ($modlist as $m) {
				if ($m->__syscalled) $m->formSave();
			}
		}


		echo CForm::ajaxReturn();

	}

	public function actValid() {

		$fmSubmit = $this->DOC->get('page.fmSubmit');
		$_pageMode = $this->DOC->get('page.mode');

		if ($_pageMode != '' && $fmSubmit == 'yes') {

			// 1. form itself checking
			$msg = CForm::validate();

			// 2. page valid
			$ret = $this->pObj->formValid();
			$msg = array_merge($msg, ( is_array($ret) ? $ret : array()) );

			// 3. modules valid
			$mods = prog::getModules();
			foreach ($mods as $modlist) {
				foreach ($modlist as $m) {
					if ($m->__syscalled)
						$msg = array_merge($msg, $m->formValid());
				}
			}

			if (count($msg) > 0) {
				echo json_encode(array( 'ret' => array( 'status' => false, 'msg'	=> $msg )));

				exit;
			}

			return TRUE;
		}

		return FALSE;

	}

	public function loadCache($page, $layout, $id)
	{
		$id = ($id=='') ? '' : '.' . $id;
		return cache::get('page/' . $page, $layout . $id . '.modules.json');
	}

	public function storeCache($page, $layout, $id, $data)
	{
		$id = ($id == '') ? '' : '.' . $id;
		return cache::set('page/' . $page, $layout . $id . '.modules.json', $data);
	}

	public function loadModule($mods = NULL)
	{
	    // $obj = &prog::$OBJ;
		$page	= $this->DOC->get('app.args.page');
		$param	= $this->DOC->get('app.args.param');

		$layout	= $this->DOC->get('page.html.layout');

		$id		= is_array($param)?$param[0]:'';

		if (!$mods) {
			$mods	= self::loadCache($page, $layout, $id);

			if (!$mods) {
				$mods	 = cache::loadModule($page, $layout);  // json_decode(file_get_contents(SYSDATA_PATH."/page/$page/{$layout}.modules.json"), true);
			}
		}

		$extMods = layoutMgr::get();
		$mods	 = layoutMgr::merge($mods, $extMods);

		$this->DOC->set('page.modules', $mods);
		$disabledModules = $this->DOC->get('page.disabledModules');


		//sys::print_var($mods);
        // load / new / exec modules
		$box_pos = array('front', 'inline', 'rear');
		foreach ($mods as $box => $items)
		{
			foreach ($box_pos as $pos)
			{

				if (count($items[$pos])==0) continue;

				foreach ($items[$pos] as $item)
				{

					$name = key($item);

					if (substr($name, 0, 4) != "mod_") continue;  // skip inline module, page

					if (is_array($disabledModules) && in_array($name, $disabledModules))	continue;

					$module = $item[$name];  // { "action":{ "show": {"count":10} } }

					if (array_key_exists('action', $module))
					{
						$action = $module['action'];
						$func = key($action);
						$param = $action[$func];
						$param['conf'] = $module['conf'];
						$prog   = &prog::get($name, '', $module['action'][$func]['blockId'], TRUE);
					}
					else
					{
						$func   = key($module);
						$param  = $module[$func];
						$param['conf'] = $module['conf'];
						$prog   = &prog::get($name, '', $module[$func]['blockId'], TRUE);
					}



					$csspath[] = MODULE_PATH . "/{$name}/style.css";
					$csspath[] = SITE_MODULE_PATH . "/{$name}/style.css";
					foreach ($csspath as $path) {
						if (file_exists($path)) {
							css::addFile($path);
							break;
						}
						$path = '';
					}

					$param['_box'] = $box;
					$param['_boxpos'] = $pos;

					$prog->_blockId = $module[$func]['blockId'];
					if ($prog->_blockId == NULL) $prog->_blockId = '0';

					$prog->_action = $func;

					$prog->exec($param, $func);
				}
			}
		}
	}

	public static function exec_mod($submod, $act='', $ret=FALSE){

		foreach ($submod as $mod=>$val){

			if (substr($mod, 0, 4) != "mod_") continue;  // skip inline module, page

			$prog = &prog::get($mod);

			if (array_key_exists('action', $val))
			{
				$action = $val['action'];
				foreach ($action as $func=>$param){

					if ($act == 'action')
						$prog->exec($param, $func);
					else {
						if ($ret)
							return $prog->render($func, $param, $ret);
						else
							$prog->render($func, $param);
					}
				}
			} else {
				foreach ($val as $func=>$param){

					if ($act == 'action')
						$prog->exec($param, $func);
					else {
						if ($ret)
							return $prog->render($func, $param, $ret);
						else
							$prog->render($func, $param);
					}
				}
			}
		}

	}

	public function ajax($args)
	{
		global $m;
		$args['param'] = array_filter($args['param']);
        $this->DOC->set('app.args', $args);

        // ajax/sys.pages.media/online/1234
	    $ary = explode(".", $args['action']);
	    $dir = lib::htmlChars( implode("/", $ary) );


		if (($ary[0]!='sys' && $ary[0]!='site') ||
			!in_array($ary[1], array('pages', 'modules') ) )
		{ echo "{\"err\": \" wrong url!\"}"; exit(); }

		// necessary fields.
		$ajaxUrl = WEB_ROOT . '/' . $args['page'] . '/' . $args['action'] . '/' . $args['param'][0] . '/';


		$_lock = args::get('_lock', 'string');

		// if ($_lock=='') {
			// echo "{\"err\": \" _lock is empty!\"}";
			// exit();
		// }

		$_fields = preg_split('/,/', $_lock);

		foreach($_fields as $f) {
			$_vals[$f] = args::get($f, 'nothing');
		}

		$ajaxAuth = args::get('ajaxAuth', 'string');
		if ($_lock!='' && lib::ajaxAuth( $ajaxUrl, $_vals ) != $ajaxAuth ) {

			if (__DEBUG__) {
				print_r( array( 'ajaxAuth' => $ajaxAuth,
								'_url' => $ajaxUrl,
								'_lock' => $_lock,
								'fields' => $_fields,
								'vals' => $_vals,
								'server-computing-auth' => lib::ajaxAuth( $ajaxUrl, $_vals )));

				echo "{\"err\": \"You must use lib::lockUrl() to secure your ajax post!\"}";

			}
			else
			{
				echo json_encode(array( 'ret' => array( 'status' => 'false', 'msg'	=> $m['logoutRefreshPS'] )));
			}

			exit();
		}

        $param = explode(".", array_shift($args['param']));

        if (count($param) > 0)
        {
            $axName = $param[0];
            $param  = $param[1];
		}
        else
        {
            $axName = array_pop($ary);
            $param  = $param[0];
        }

	    $file = "{$dir}/{$axName}.php";

	    if (!file_exists($file)) {
			echo "{\"err\": \" {$file} not found!\"}";
			exit;
        }

	    require_once($file);

        $axFunc = "ax_{$param}";

	   // $action = array_shift($param);

	    $this->DOC->set('app.args', array('page' => $axName, 'action' => $action, 'param' => $param));
	    $ctrl = new $axName($file, 'ajax', $this);
	    $ctrl->$axFunc($args['param']);
	}

	public function render()
	{
	    switch ($this->DOC->get('page.type')) {
	        case 'html':
	            $obj = new Html($this->page);
				break;

	        case 'rss':
				require_once('rss.php');
	        	$obj = new Rss($this->page);
	        	break;

			case 'ipage':

				$obj = new Html($this->page, TRUE);
				break;

	        case 'itune':
	        case 'subscribe':
	    }

	    if (!empty($obj)) {
	    	$obj->render();
	    }
	}


	public function loadPageScript() {

		css::addFile(CSS_PATH . '/font-awesome.min.css');
		css::addFile(CSS_PATH . '/bootstrap.min.css');
		css::addFile(CSS_PATH . '/style.css');

		css::addFile(SITE_PATH . '/style.css');

		// INFO: Loading theme's style.css
		// $colorStylePath = $this->DOC->get('page.style.color');
		// css::addFile($colorStylePath);
		// $this->DOC->set('page.style', $colorStylePath);

		$ver = lib::detectIEversion();

		js::addlib (
			( $ver > 0 && $ver < 9 )
				? JS_PATH . '/jquery.1.9.1.js'
				: JS_PATH . '/jquery.2.0.0.js'
		);

		js::addlib(JS_PATH . '/jquery-ui-1.10.3.custom.min.js');
		js::addlib(JS_PATH . '/jquery.ui.touch-punch.min.js');

		js::addlib(JS_PATH . '/jquery.evt.js');
		js::addlib(JS_PATH . '/lib.js');
		js::addlib(JS_PATH . '/bootstrap.min.js');
		js::addlib(JS_PATH . '/bootbox.js');
		js::addlib(JS_PATH . '/tmpl.min.js');


		// js::addlib('//www.google-analytics.com/ga.js');


		// fancybox
		js::addlib(JS_PATH . '/jquery.fancybox.js');
		css::addFile(CSS_PATH . '/jquery.fancybox.css');

		$jsPath = JS_PATH;

		// popupLinkfy
		js::addLast(<<<link

		$.Evt('popupLinkfy').subscribe(function(){

			$('a[target="_popup"]').each(function(idx, itm){

				var _size = $(this).attr('rel').split('x');

				_size[0] = (_size[0] == 'auto') ? 760 : _size[0];
				_size[1] = (_size[1] == 'auto') ? 500 : _size[1];

				var parser	= document.createElement('a');
				parser.href = $(this).attr('href');

				var _param = (parser.search == '') ? '?popup=true&output=embed' : '&popup=true&output=embed';
				parser.search += _param;

				$(itm).fancybox({
					type		: 'iframe',
					preload		: true,
					autoSize	: false,
					href		: parser.href,
					padding		: 5,
					title		: function(){
						return $('<a>', {
							href	: $(this).attr('href'),
							html	: $(this).attr('href'),
							target	: '_blank'
						}).wrapAll('<tmpl>').parent().html();
					},
					titleShow	: true,
					margin		: 20,
					beforeLoad: function(){
						this.width  =  parseInt(_size[0], 10);
						this.height =  parseInt(_size[1], 10);
					}
				});

				$(this).removeAttr('target');
			});
		});

link
);

		js::addLast(<<<trigger

		var _windowLoadEvents = new Array('popupLinkfy', 'ExtUrllinkfy');

		function removeWindowLoadEventsEvent (e){
			var index = _windowLoadEvents.indexOf(e);
			_windowLoadEvents.splice(index, 1);
		}

		$(function(){
			for(var e in _windowLoadEvents) {
				$.Evt(_windowLoadEvents[e]).publish();
			}
		});
trigger
);
	}

}