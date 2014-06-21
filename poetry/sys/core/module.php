<?php
class Module {

	// MARK: New API
	private $_pId;
	public $_blockId = '0';
	public $_action = '';

	public static $_pageMode;

	public $_form;

	protected $App;
    protected $dir;
	protected $mode;
    protected $args;
    protected $scripts;
    protected $styles;
    protected $header_script;

    protected $cacheDir;

	protected $toolbox;

	protected $_box;
	protected $_box_pos;

	protected static $pageId;

    protected $breadcrumb;

	protected $data;

	public function __construct($file, $mode, $app)	{

		static $pIdCounter = 0;
		$this->_pId = $pIdCounter++;
		$this->App = &$app;
		$this->mode = $mode;
		$this->dir = dirname($file);
		$this->args = $this->App->DOC->get("app.args");

		switch($mode) {

			case 'module':

				$mod = explode('/', $this->dir);
				$this->cacheDir = "module/" . $mod[count($mod) - 1];

				break;

			default:

				$page = preg_split('/_/', @$this->args['page']);
				$this->cacheDir = "page/" . $page[0];

				break;
		}

	}

	// MARK: New API
	public function getPID() { return $this->_pId; }
    
    public function getPageObj() {
        return $this->App->pObj;
    }
    
	protected function getArgs($id, $type, $def = NULL) {

		$id = CForm::getId($this, $id);
		return args::get($id, $type, $def);
	}

	public function formSave() {
		//echo get_class($this) . '::save() ';
		return array();
	}

	public function formValid() {

		//echo get_class($this) . '::valid() ';
		return array();
	}

	protected function formReturn($id, $msg, $action = '', $ext = '') {

		$ret = array('id' => CForm::getId($this, $id), 'msg' => $msg);
		if ($action != '') {
			$ret['action'] = $action;
			$ret['ext'] = $ext;
		}
		return $ret;
	}

	protected static function _setPageId($pageId) {
		self::$pageId = $pageId;
	}

	protected static function _getPageId() {

		if (self::$pageId=='') {
			$app = prog::getApp();
			$pageId[]      	= $app->DOC->get('app.args.page');
			// $pageId[]      	= $this->App->DOC->get('app.args.action');
			$param       	= $app->DOC->get('app.args.param');
			$pageId[]		= is_array($param)?$param[0]:'0';
			self::_setPageId( implode('.', array_filter($pageId)) );
		}

		return self::$pageId;
	}

	public function getPageId() {
		return self::_getPageId();
	}

    public function breadcrumb( $data ) {
	
	
    }

	public function exec($args, $action) {
	    $func = "ac_{$action}";

		if(!method_exists($this, $func)) {
			sys::callstack();
		}
	    $this->$func($args);
	}

	public function priv($args, $action) {
        return $action;


		// if ($action == '') return true;

		// $func = "pv_{$action}";

		// if (method_exists($this, $func))
		// 	return call_user_func_array(array($this, $func), array($args, $action));
		// else
		// 	return $action;
	}

	public function render($action, $args, $ret=FALSE)	{
	    $view = "vw_{$action}";

		if ($ret == TRUE)
			return $this->$view($args);
		else
			$this->$view($args);

		return NULL;
	}

	public function ajax($action, $args) {
	    $func = "ax_{$action}";
	    $this->$func($args);
	}

	public function ajaxReturn($status, $msg='ok', $focus='', $adt=array()) {

        if (count($adt) == 0) return json_encode(array('ret'=> array('status' => $status, 'msg' => $msg, 'focus' => $focus)));

        $ary = array('status' => $status, 'msg' => $msg, 'focus' => $focus);
        return json_encode(array('ret'=> array_merge($ary, $adt)));
    }

	public function errorModelMessage($msg) {

		return array(
            'type'    => 'html',
            'html'    => array('title' => "ERROR [{$msg}]",
                'layout'   => array('xbox' => array(array('mod_error' => array('modalShow' => '')))),
                'template' => 'modal'),
            'content' => array('error' => $msg)
        );
    }

	public function errorMessage($title, $msg='') {

		$backToHomePage = theme::l($m['retToHomepage'], '/');
        if($msg=='') $msg = $title;

		//TODO: dont use default template
        return array(
            'html'  => array('title'    => "{$m['error']} [{$title}]",
                             'layout'   => array('xboxBanner' => array(array('mod_error' => array('show' => '')))),
                             'template' => 'error'
            ),
            'content' => array('error' => $msg . "<br/>" . $backToHomePage)
        );
    }

    private function _inc($base, $files) {
        foreach ($files as $file) {
	        require_once($base . '/' . url::clear($file));
        }
    }

	public function includeFile() {
	    $files = func_get_args();
	    $this->_inc($this->dir, $files);
	}

	public function includeClass() {
	    $files = func_get_args();
	    $this->_inc(CLASS_PATH, $files);
	}

	public function includeModule() {
	    $files = func_get_args();
	    $this->_inc(MODULE_PATH, $files);
	}

	public function includeLib($file) {
	    $files = func_get_args();
	    $this->_inc(LIB_PATH, $files);
	}

	public function addFileEx($type, $fileName) {

		return $this->addFile($type, $fileName, '/'.$this->dir);
	}

	public function addFile($type, $fileName, $filePath = '') {

		switch(strtolower($type))
		{
			case 'css':
				if($filePath == '')
					$filePath = CSS_PATH . "/{$fileName}";
				else
					$filePath = $filePath . "/{$fileName}";

				CSS::addFile($filePath);
				return TRUE;
				break;
				
			case 'js':
						
				if($filePath == '')
					$filePath = JS_PATH . "/$fileName";
				else
					$filePath = $filePath . "/$fileName";

					sys::print_var($filePath);
				JS::addlib($filePath);
				return TRUE;
				break;
				
				
			default:
				break;
		}

		return FALSE;
	}

	public function addUI() {
        js::addlib(JS_PATH . '/lib.js');
	}

	public function addMenu($parent, $item) {
		$id = NULL;
		if(is_array($item) &&array_key_exists('id', $item))
		{
			$id = $item['id'];
			unset($item['id']);
		}
		return CMenu::add($parent, $item, $id);
	}

	public function getMenu($target = NULL) {

		$root = CMenu::get("sys.page.modules", NULL);

		$node = CMenu::get($root, "__pid_{$this->getPID()}");
		if(is_null($node)) $root = CMenu::add($root, array('text' => 'module'), "__pid_{$this->getPID()}");

		$node = CMenu::get($root, "__action_{$this->_action}");
		if(is_null($node)) $node = CMenu::add($root, array('text' => 'action'), "__action_{$this->_action}");

		return CMenu::get($node, $target);
	}

	public function getPageMenu($target = NULL) {
		return self::__getMenu('sys.page.admin', $target);
	}

	protected static function __getMenu($basePath, $target) {

		return CMenu::get($basePath, $target);
	}

	// ISSUE: Incomleted
	public function serialize($layoutModule) {
	// INFO: Pack the stored data into json
		if(is_null($layoutModule)) return NULL;

		$args = json_decode($layoutModule['args'], TRUE);
		$conf = json_decode($layoutModule['conf'], TRUE);
		return array('module' => $layoutModule['module'],
					 'action' => $layoutModule['action'],
					 'args' => $args ? $args : '',
					 'conf' => $conf ? $conf : '');
	}

	// INFO: System API
	public static function prepareSerialData($dbLayoutModule, $themeItemIdentifier = NULL, $forceOverride = FALSE) {

		$module = prog::get($dbLayoutModule['module'], '', $dbLayoutModule['id']);

		$moduleParam = $module->serialize($dbLayoutModule);

		if($moduleParam === NULL) return NULL;

		if(is_string($themeItemIdentifier))
		{
			if($forceOverride)
				$moduleParam['conf']['id'] = $themeItemIdentifier;
			else
				$moduleParam['conf']['id'] = $moduleParam['conf']['id'] ? $moduleParam['conf']['id'] : $themeItemIdentifier;
		}

		return self::jsonize($moduleParam);
	}

	public static function jsonize($serializedContent) {

		return array($serializedContent['module'] =>
					 array('action' => array($serializedContent['action'] => $serializedContent['args']),
						   'conf'	=> $serializedContent['conf'])
		);
	}

	private function user_access($action, $pageId = '', $role = '') {

		if ($pageId=='') $pageId = $this->_getPageId();
		if (!$role) $role = profile::$role;

		// sys::print_var(profile::$role);
		// sys::print_var($pageId . ' - ' . $action, $role);
		// sys::print_var( Privilege::check($action, $pageId, $role), "($action, $pageId, $role)");
		return Privilege::check($action, $pageId, $role);
	}

	public function readable($pageId = '', $role = '') {
		return $this->user_access('view', $pageId, $role);
	}

	public function editable($pageId = '', $role = '') {
		return $this->user_access('edit', $pageId, $role);
	}

	public function deletable($pageId = '', $role = '') {
		return $this->user_access('delete', $pageId, $role);
	}

	public function creatable($pageId = '', $role = '') {
		return $this->user_access('create', $pageId, $role);
	}

	public function privable($pageId = '', $role = '') {
		return $this->user_access('priv', $pageId, $role);
	}

	public function setCacheDir($dir) {
		if (!empty($dir)) $this->cacheDir .= "/$dir";

	}

	public function cachefile($key = '') {
		$key = ($key=='')?$this->_id:$key;
		return cache::getfile($this->cacheDir, $key);
	}

	public function loadCache($key = '') {

		$key = ($key=='')?$this->_id:$key;
		return cache::get($this->cacheDir, $key);
	}

	public function cache($data, $key = '') {
		$key = ($key=='')?$this->_id:$key;
		return cache::set($this->cacheDir, $key, $data);
	}

	public function clearCache() {
		cache::clear($this->cacheDir, $this->_id);
	}


	public function docPath($ext = NULL) {

		return (!$ext) ? 'modules.' . get_class($this) . '.' . $this->_blockId
					  : 'modules.' . get_class($this) . '.' . $ext;
	}
	
	

//END SEC///////////////////////////////////////////////////////////////////////////////////////////////////////////////
}

class css {


    public static function add() {

		$app = prog::getApp();
        $css  = &$app->DOC->get('css.style');
        $args = func_get_args();

        foreach ($args as $arg) $css[] = $arg;
    }

    public static function addFile() {

		$app = prog::getApp();
		$styles = &$app->DOC->get('css.lib');
        $args   = func_get_args();

        foreach ($args as $arg) $styles[] = $arg;

		$styles = array_unique($styles);
    }
}

class js {
    public static function addlib() {
//js lib
		$app = prog::getApp();
        $js     = &$app->DOC->get('js.lib.header');
        $args   = func_get_args();

        foreach ($args as $arg) $js[] = url::clear($arg);

		$js = array_unique($js);
    }

    public static function addlibFooter() {
//js footer lib
	$app = prog::getApp();
        $js     = &$app->DOC->get('js.lib.footer');
        $args   = func_get_args();

        foreach ($args as $arg) $js[] = url::clear($arg);

		$js = array_unique($js);
    }

    public static function add() {
//js script
		$app = prog::getApp();
        $js     = &$app->DOC->get('js.src');
        $args   = func_get_args();

        foreach ($args as $arg) $js[] = $arg;
    }

    public static function addLast() {
//js script
		$app = prog::getApp();
        $js     = &$app->DOC->get('js.srcLast');
        $args   = func_get_args();

        foreach ($args as $arg) $js[] = $arg;
    }

    public static function dynamicLoad() {
//js dynamic load

		$app = prog::getApp();
        $js     = &$app->DOC->get('js.dynamic');
        $args   = func_get_args();

        foreach ($args as $arg) $js[] = $arg;
	}

    public static function template() {
//js script
		$app = prog::getApp();
        $js     = &$app->DOC->get('js.template');
        $args   = func_get_args();

        foreach ($args as $arg) $js[] = $arg;
    }

}