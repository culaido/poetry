<?php

require_once(dirname(__FILE__) . '/session.php');
require_once(dirname(__FILE__) . '/app.php');

db::init();

new session(SESS_LIFE);

if (defined('COOKIE_DOMAIN')) {
	session_set_cookie_params ( 0, '/', COOKIE_DOMAIN );
}

function _init() {

	mb_internal_encoding("UTF-8");

    log::_init();
    args::_init();
    cookie::init();
	
    return new App( url::parse() );
}


class db {

    private static $pdo = NULL;
    private static $stmt = NULL;
	private static $_stat;

    public static function init()
    {
		if (self::$_stat['init']) return;
		self::$_stat = array('init' => 0, 'select' => 0, 'fetch' => 0, 'query' => 0, 'multiQuery' => 0);
		self::$_stat['init']++;
        if (is_object(self::$pdo)) return;  // share db connection within a page

        try { self::$pdo = new PDO(DB_DSN, DB_USR, DB_PWD, array(PDO::ATTR_PERSISTENT => TRUE)); }
        catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }

        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);  // default: ERRMODE_SILENT
		self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->query("SET NAMES 'utf8'");

		$timezone = cookie::get('timezone', 'string');
		if ($timezone=='') $timezone = 8;

		if ($timezone>=(-12) && $timezone<=(+13))
		{

			require_once ROOT_PATH . '/' . RES_PATH . '/timezone.php';
			$tz = ($timezone>=0) ? sprintf("+%1$02d:00", $timezone) : sprintf("%1$03d:00", $timezone);

			// set PHP timezone
			date_default_timezone_set($__TIMEZONE__[$timezone]);

			// set MySQL timezone
			self::$pdo->query("SET time_zone = '{$tz}'");

		}
    }


    public static function select($sql, $params=array(), $buffered = TRUE)
    {
		self::$_stat['select']++;

        if (count($params) == 0) {
            self::$stmt = self::$pdo->query($sql);
            return self::$stmt;  // return a result set as a PDOStatement object
        }

        self::$stmt = self::$pdo->prepare($sql, array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => $buffered));

        if ( self::$stmt->execute($params) === FALSE)
			self::showErr($sql, $params);

        return self::$stmt;
    }

    public static function fetch($sql, $params=array())
    {
		self::$_stat['fetch']++;
        $stmt = self::select($sql, $params);
        return $stmt->fetch();
    }

	public static function showErr($sql, $params) {

		$param_str = print_r($params, TRUE);

		ob_start();
		debug_print_backtrace();
		$trace = ob_get_contents();
		ob_end_clean();

		$dberr = self::$stmt->errorInfo();


		//////////////////////////////////
		// do filelog (might be no lib class can be used.
		$caller = array_shift(debug_backtrace());
		$info = "file: {$caller['file']} line: {$caller['line']}";

        $fp = fopen(ROOT_PATH . "/log.txt", "a");

		$text = $trace . "\n SQL: " . $sql . "\n" . $param_str;
        fwrite($fp, "[{$info}] " . date("Y-m-d H:i:s") . ":\n {$text}\n");

		$text = "\n Error: " . $dberr[2];
        fwrite($fp, "[{$info}] " . date("Y-m-d H:i:s") . ":\n {$text}\n");

        fclose($fp);
		/////////////////////////////////////

		if (__DEBUG__) {
			sys::print_var($sql, 'db::query');
			sys::print_var($params, 'db::query (param)');
			sys::print_var($dberr[2], 'Err');
		}
	}


    public static function query($sql, $params=array(), $buffered = TRUE)
    {
		self::$_stat['query']++;
        if (count($params) == 0) {
            self::$stmt = self::$pdo->exec($sql);
            return self::$stmt;  // return the number of affected rows
        }

        self::$stmt = self::$pdo->prepare($sql,	array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => $buffered));

		if (self::$stmt->execute($params)===FALSE)
			self::showErr($sql, $params);

		// sys::print_var($params, $sql);
        return self::$stmt->rowCount();
    }

	public static function multiQuery($table, $values, $dupFields=array() )
	{
		if (!is_array($values) || count($values)==0) return;

		self::$_stat['multiQuery']++;
		foreach($values as $vals)
		{
			if (!isset($fields))
			{
				$fields = array_keys($vals);
				$f_str = join(',', $fields);
			}

			$tuples[] = '(' . join(',', str_split(str_repeat('?', count($fields)))) . ')';

			foreach($vals as $v) $raw[] = $v;
		}

		$v_str = join(',', $tuples);
		$dup = array();
		foreach($dupFields as $f) $dup[] = "{$f} = VALUES({$f})";

		$onDup	 = join(',', $dup);
		$s_onDup = ($onDup=='') ? '' : (' ON DUPLICATE KEY UPDATE ' . $onDup);
		db::query("INSERT INTO {$table}({$f_str}) VALUES {$v_str} {$s_onDup}", $raw);
	}

    public static function lastInsertId() { return self::$pdo->lastInsertId(); }  // portable issue
    public static function rowCount()     { return self::$stmt->rowCount();    }  // portable issue
	public static function getStat()	  { return self::$_stat; }

}


class sys {

    public static function init()
    {
		// avoid . in the last domain
		if (substr($_SERVER['HTTP_HOST'], -1)=='.') {
			$url = url::getSite();
			url::redir( substr( $url, 0, strlen($url)-1 ) ) ;
		}

		// set the cookie domain
		session_set_cookie_params ( 0, '/', COOKIE_DOMAIN );

		////////////////////////////////////////////////
        global $m;

		$m = array();

		///////////////////////////////////////////////
        $inc_classes = array('lib', 'cache', 'profile', 'doc', 'module', 'page', 'theme', 'html', 'layoutMgr', 'privilege', 'breadcrumb');
        foreach($inc_classes as $class) {
        	require_once CORE_PATH . "/{$class}.php";
        }

        $app = _init();

		$_SESSION['auth'] = !isset($_SESSION['auth']) ? uniqid('', TRUE) : $_SESSION['auth'];

		//////////////////////////////////////////////////
        profile::_init();

		require_once( ROOT_PATH . '/sys/res/locale/locale.php' );

        // locale check
        $args = url::parse();

		switch ($args['page'])
        {
            case 'ajax':

				$app->ajax($args);
                break;

			case 'api':

				$app->api($args);

				break;

            default:

				if ( profile::$id==0 && !in_array($args['page'], array('index', 'maintain')) ) 
					$app->DOC->set('app.args.page', 'index', TRUE);

                $cache_file = $app->loadPage();
                $app->loadModule();

				cache::overwrite($cache_file, $app->DOC->get(NULL));

				if ($app->actValid())
					$app->actSave();
                else
					$app->render();

				//	sys::print_var(db::getStat());
                break;
        }
    }

	public static function getCfg($name, $def = NULL) {

		if (isset(profile::$sysconfig[$name])) return profile::$sysconfig[$name];

		$obj = db::fetch('SELECT * FROM sysconfig WHERE name=?', array($name));

		if (!$obj) {
			// if ($def) self::setCfg($name, $def);
			return $def;
		}

		profile::$sysconfig[$name] = $_SESSION['_sess_sysconfig'][$name] = $obj['value'];
		return $obj['value'];

	}

	public static function setCfg($name, $value) {


		$cfg = self::getCfg($name);

		$refreshAll = FALSE;
		if ($cfg === NULL) {
			db::query('INSERT INTO sysconfig SET name=:name, value=:value', array(':value'=>$value, ':name'=>$name));
			$refreshAll = TRUE;
		} else {

			if ($cfg!=$value) {
				db::query('UPDATE sysconfig SET value=:value WHERE name=:name', array(':value'=>$value, ':name'=>$name));
				$refreshAll = TRUE;
			}
		}

		if ($refreshAll) session::refreshAll();

		profile::$sysconfig[$name] = $_SESSION['_sess_sysconfig'][$name] = $value;
	}

	// ** 在 cron 強制刪除時使用
	public static function forceDeleteDir($path){

		//sys::print_var("forceDeleteDir($path)");
		if ($path == '') return;

		//       1       2   3 4  5   6
		// ex: sysdata/users/1/1/doc/...
		if ( count( explode('/', $path) ) < 3 ) return;

		$dir = ROOT_PATH ."/{$path}";

		//sys::print_var("folder exist?");
		if (!file_exists($dir)) return;

		log::save('file', 'delete', $path, "forceDeleteDir");

		exec("rm -rf {$dir}");
		//sys::print_var("rm -rf {$dir}");
	}

	public static function deleteUserDir($path, $userId='') {

		if ($userId === '') $userId = profile::$id;

		// due to if the path isn't exist, it will be null.
		if ($path == '') return;
		$vol = lib::userHash($userId);

		// check path prefix must be user's folder
		$prefix = "sysdata/users/{$vol}/{$userId}";

//lib::filelog('a path: ' . $path . '--------- prefix: ' . $prefix);

		if ( strcmp($prefix, substr($path, 0, strlen($prefix))) != 0 )	return;

		// to delete user folder, it must under 2 levels by user folder
		// only 2 depths folder can be deleted. ( doc/xxx )
//lib::filelog('b path: ' . $path . ': ' . count(explode("/", $path)) . '--- prefix: ' . $prefix . ':' . count(explode("/", $prefix)));

		if ( (count(explode("/", $path)) - count(explode("/", $prefix))) < 2 )	return;

//lib::filelog('c');

		$folder = ROOT_PATH ."/{$path}";
		if (!file_exists($folder)) return;
//lib::filelog('d');
		log::save('file', 'delete', $path, "delete by " . profile::$account);

//lib::filelog($folder);
		exec("rm -rf {$folder}");
	}


	public static function mkdir($path)	{
		if (!is_dir($path)) @mkdir($path, 0755, TRUE);
	}

	public static function exception($msg, $title='')
	{

	    $caller = array_shift(debug_backtrace());
		$info = "file: {$caller['file']} line: {$caller['line']} ";
        echo "<div style='text-align:left; margin:10px; border:1px solid #f00; padding:5px; background:#faa'><i>$info</i><BR /><b>$title</b>";
        echo "<pre>";
        print_r($msg);
		echo "\r\n\r\n";
		if (__DEBUG__)	debug_print_backtrace();		// TODO: need to remove after get online
        echo "</pre>";
        echo "</div>";
	    exit;
	}

    public static function err($err, $file = "", $line = "", $stop = true)
	{
	    if ($file != "") $err = "{$err}, in $file (line: $line)";

        sys::log($err, "err");
        echo "$err";

		if ($stop) die();
	}

	public static function print_var($data, $title="")
    {
		$caller = array_shift(debug_backtrace());

		$info = "file: {$caller['file']} line: {$caller['line']} ";

        echo <<<DIV
		<div style='text-align:left; margin:10px; border:1px solid #f00; padding:5px; background:#ffc; z-index:99999; position:relative;'>
			<i>{$info}</i><BR /><b>$title</b>
DIV;
            echo "<pre>";
                print_r($data);
            echo "</pre>";
        echo "</div>";
    }

	public static function callstack($return = FALSE)
	{
        ob_start();
	   echo <<<DIV
		<div style='text-align:left; margin:10px; border:1px solid #f00; padding:5px; background:#ffc; z-index:99999; position:relative; max-width:200px;'>
			<b>call stack</b>
DIV;
        echo "<pre>";
		echo "\r\n\r\n";
		if (__DEBUG__)	debug_print_backtrace();		// TODO: need to remove after get online
        echo "</pre>";
        echo "</div>";
        $content = ob_get_contents();
        if ($return) {
            return $content;
        } else {
            echo $content;
        }
	}
}


class url {

	public static function getSite() {
		return self::protocol() . "://" . $_SERVER['HTTP_HOST'];
	}

    public static function protocol() {
        return strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
    }

	public static function getURI() {
		return $_SERVER['REQUEST_URI'];
	}

    public static function get() {
        $protocol = self::protocol();
        return "{$protocol}://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    public static function verifyDomain($url) {
        $protocol = self::protocol();
		$http_host = $protocol . '://' . $_SERVER['HTTP_HOST'];

        return (strpos($url, $http_host) === 0) ? TRUE : FALSE;
    }

    public static function parse()
    {
        // get and clear url
        $route = substr($_SERVER['REQUEST_URI'], 1);
		$route = preg_replace("/\\?.*/", "", $route);  // ** allow http://.../media/list/?page=2&mode=3


        //administrator is home so route can't be home (change to index)
    	if ( empty($route) || '/' . $route == WEB_ROOT . '/' ) 
			$route = WEB_ACCOUNT . '/index';
			
		if ( strpos('/' . $route, WEB_ROOT . '/') === 0 ) {
			$route = substr($route, strlen(WEB_ROOT), strlen($route));
		}

		$param  = explode('/', $route);

		if ( count($param)==1 ) $param = array('index');
		$page   = array_shift($param);

		$action = (@is_numeric($param[0]))
			? ''
			: basename(array_shift($param)); // http://powercam.cc/media/123, http://powercam.cc/user/login

		return array("page" => $page, 'action' => $action, "param" => $param);
    }

	public static function redir($url, $time=0)
	{
		if (!$url) exit;
        $time = is_int($time) ? $time : 0;

		// echo $url;die();

		if (!headers_sent() && !$time) {
			header('Content-Type:text/html; charset=utf-8');
			header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			header("Location: {$url}");
		}
		else
		{
			js::add(<<<JS
            (function(){
                setTimeout(function(){
                    window.location.href='{$url}';
                }, {$time} * 1000);
            })();
JS
);
		}

		exit;

	}

    public static function clear($path) { return preg_replace("/\\.\.\/|\.\/|sysdata|scip/", "", $path); }

	public static function request($url, $post=array(), $param=array()){

		$ch = @ curl_init($url);

		$default = array(
			CURLOPT_SSL_VERIFYPEER  => 0,
			CURLOPT_SSL_VERIFYHOST  => 0,
			CURLOPT_FOLLOWLOCATION	=> 1,
			CURLOPT_RETURNTRANSFER	=> 1,
			CURLOPT_TIMEOUT			=> 10
		);

		if (count($post)>0) {
			curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $post ));
		}

		$param += $default;
		foreach ($param as $idx=>$val) @ curl_setopt($ch, $idx, $val);

		return @ curl_exec($ch);
	}
}










class args {
    public static $args;

    public static function _init() {
        self::$args = array();
        self::$args = array_merge(self::$args, (array)$_GET, (array)$_POST);
        self::unset_post_get();
    }

    private static function unset_post_get($_argDebug = 1)
    {
		global $_POST, $_GET, $GLOBALS;
        if ($_argDebug) // debug mode
        {
            if (ini_get('register_globals'))
            {
                $clear_arr = array($_POST, $_GET);
                foreach ($clear_arr as $arr)
                {
                    if ($arr)
                    {
                        foreach ($arr as $key => $value) {
							unset($GLOBALS[$key]);
                        }
                    }
                }
            }
        }

        unset($_POST);
        unset($_GET);
    }

	public static function getAll() {
		return self::$args;
	}

    public static function get($name, $type='int', $default=NULL)
    {
        //if (!isset(self::$args[$name]))
          //  return ($type == 'white_list') ? $default[0] : $default;
     //   	if($name == 'media_0_deadline') {
	    // 	$debug = debug_backtrace();
	    // 	lib::filelog( "call from:  ". print_r($debug[0], TRUE));
    	// }


        $value = self::$args[$name];
        // lib::filelog("$name, $type," . json_encode($value));
		return self::clear($type, $value, $default);
    }

    public static function clear($type, $value, $default=NULL) {


        switch ($type) {
            case 'url':
             	return lib::htmlChars(urldecode($value));
             	break;
            case 'int':
                if (!is_int($default)) {
                	$default = 0;
                }
                return is_numeric($value)
                            ? intval($value)
                            : intval($default);
                break;
            case 'float':
                if (!is_float($default)) {
                	$default = 0.0;
                }
                return is_numeric($value)
                                    ? floatval($value)
                                    : floatval($default);
                break;
            case 'string'    :
             	if ($default===NULL) {
             		$default = '';
             	}
        		return (trim($value) === '')
        					? $default
        					: trim($value);
				break;
            case 'white_list':
        		return (in_array($value, $default))
        					? $value
        					: $default[0];
				break;
            case 'editor'    :
                $tmpResult =  lib::htmlPurifier($value);
                return preg_replace('/^((\r|\n)*<div>(\s|&nbsp;|\xC2\xA0)*(<br \/>)?<\/div>(\r|\n)*)+|((\r|\n)*<div>(\s|&nbsp;|\xC2\xA0)*(<br \/>)?<\/div>(\r|\n)*)*$/', "", $tmpResult);
                break;

            case 'precedence':
				if ($value=='') return $default;
            	return (strtoupper($value) === "DESC")
            				? "DESC"
        					: "ASC";
				break;
            case 'nothing':
            	return ($value === NULL || $value === '')
            				? $default
            				: $value;
				break;
            default:  // throw error, switched parameters in optional_param or another serious problem
                sys::exception("wrong type: {$type} ({$key}, {$value})");
                exit;
        }


    }
}


// module, page are one of a prog
class prog {

    private static $_PAGE;
	private static $_MODULES;
	private static $_HELPERS;
	private static $_app;

	public static function setApp($app) {
		self::$_app = &$app;
	}

	public static function getApp() {
		return self::$_app;
	}

    public static function &get($name, $action = '', $blockId = 0, $sys = FALSE)
    {
		// ex: {page}/{action}

		if (strpos($name, '_') === FALSE) {

			if (!is_object( @self::$_PAGE[$name] )) {
				$file = self::incFile($name, $name);
				$obj = new $name($file, 'page', self::$_app);
				$obj->__syscalled = $sys;
				self::$_PAGE[$name] = $obj;
			}

			if ($sys) self::$_PAGE[$name]->__syscalled = TRUE;

			return self::$_PAGE[$name];
		}
		else {
			// avoid load error page, ex: url: mobile/home, page = mobile, action = home
			$item = explode('_', $name);
			if ($item[0] == 'mod') {	// proc mod prefix

				$blockId = ($blockId=='') ? 0 : $blockId;

				if (!is_object( @self::$_MODULES[$name][$blockId] ))	{
					$file = self::incFile($name, $name);
					$obj = new $name($file, 'module', self::$_app);
					$obj->__syscalled = $sys;
					self::$_MODULES[$name][$blockId] = $obj;
				}

				if ($sys) self::$_MODULES[$name][$blockId]->__syscalled = TRUE;

				return self::$_MODULES[$name][$blockId];
			}
 		}
    }

	public static function getModules() {
		return self::$_MODULES;
	}

	public static function helper($helper) {
		$name = $helper . '_helper';
		$file = HELPER_PATH . '/' . $name . '.php';

		if (!is_object( self::$_HELPERS[$name] )) {
			if (file_exists($file)) {
				require_once($file);
				self::$_HELPERS[$name] = new $name();
			} else {
				sys::exception("file '$file' not found! helper({$helper})");
			}
		}
		return self::$_HELPERS[$name];
	}

    public static function invoke($name, $func, $args)
    {
		$obj = &self::get($name, '');
        $obj->$func($args);
    }

    public static function incFile($name, $filename = '', $dirs=array(PAGE_PATH, MODULE_PATH, SITE_PAGE_PATH, SITE_MODULE_PATH) )
    {
        // *** get from cache (name, path) for performance issue, name shoud be unique as $name is a class
        foreach ($dirs as $dir) {

			$relPath = "{$dir}/{$name}/{$filename}.php";
			$file = ROOT_PATH . "/". $relPath;

            if (file_exists($file)) {
                require_once($file);
                return $relPath;
            }
        }

		sys::exception("file '$name' not found! incFile($name, $filename)");
        return false;
    }
}

class event {

    public static function trigger($event, $args='')
    {
        $hooks = self::getHooks($event);
        if (count($hooks) == 0) return;

	    foreach ($hooks as $hook) {
	        $name = key($hook);
	        $func = "hk_{$hook[$name]}";

	        $obj = prog::invoke($name, $func, $args);  // auto load & instanciate
    	}
    }

    static function getHooks($event) {

        $hooks = array();
        $stmt = db::select("SELECT * FROM hook WHERE event=?", array($event));
        while ($row = $stmt->fetch()) {
            $hooks[] = array($row['module'] => $row['func']);
        }

        return $hooks;
	}
}

class cookie {
   public static function init() {}

    public static function get($name, $type='int') {
        settype($_COOKIE[$name], $type);
        return $_COOKIE[$name];
    }

    public static function set($name, $val, $expire=0) {
        //expire second
        setcookie($name, $val, $expire, '/');
    }
}


class log {

    private static $func;
    private static $act;

    public static function _init() {
        //action
        $act = array('login', 'logout',
                     'add',   'edit',   'delete',
                     'move',  'movein', 'moveout'
        );

        foreach ($act  as $val) { self::$act[]  = $val; }

		// sys::print_var($_SESSION);
    }

    public static function save($func, $act, $target, $msg='') {

		if (!in_array($act,  self::$act))  $act  = 'other';

        db::query('INSERT INTO log SET userID=:userID, user=:user, func=:func, act=:act, target=:target, msg=:msg,
                                       http_referer=:http_referer, remote_addr=:remote_addr, http_x_forwarded_for=:http_x_forwarded_for, request_uri=:request_uri, logTime=NOW()', array(
            ':userID'   => profile::$id,
            ':user'     => profile::$account,
            ':func'     => $func,
            ':act'      => $act,
            ':target'   => $target,
            ':msg'      => $msg,
            ':http_referer'         => $_SERVER['HTTP_REFERER'],
            ':remote_addr'          => $_SERVER['REMOTE_ADDR'],
            ':http_x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'],
            ':request_uri'          => $_SERVER['REQUEST_URI']
        ));
    }
}


class profile {

    public static $id;
    public static $account;
    public static $name;
    public static $email;
    public static $phone;
    public static $role;
	public static $roleAry;

	public static $sysconfig;

    public static function setSession($id='', $sess=array()) {

        if ($id) {

			$userObj = prog::get('user');
			$user	 = array_shift($userObj->get($id));

			$user_role = $user['role'] .
			$user_roleAry = explode(',', $user['role']);
			$user_roleAry[] = _ROLE::DEFAULT_ROLE;

			$user_roleAry = array_unique($user_roleAry);
			$user_role = join(',', $user_roleAry);

            //only set session
            self::$id      = $_SESSION['_sess_user_id']		= $user['id'];
            self::$account = $_SESSION['_sess_user_account']= $user['account'];
            self::$name    = $_SESSION['_sess_user_name']	= $user['name'];
            self::$email   = $_SESSION['_sess_user_email']	= $user['email'];
            self::$phone   = $_SESSION['_sess_user_phone']	= $user['phone'];
			self::$role	   = $_SESSION['_sess_user_role']	= $user_role;
			self::$roleAry = $_SESSION['_sess_user_roleAry']= $user_roleAry;
		}

		self::$sysconfig = $_SESSION['_sess_sysconfig'] = self::refreshSysConfig();

        foreach ($sess as $idx=>$val) {
            switch ($idx) {
                case 'name'  : self::$name		= $_SESSION['_sess_user_name']   = $val; break;
                case 'email' : self::$email		= $_SESSION['_sess_user_email']  = $val; break;
            }
        }
    }


	function refreshSysConfig() {

		$sysconfig = array();
		// get all sysconfig
		$sysobjs = db::select('SELECT * FROM sysconfig');
		while ($cfg = $sysobjs->fetch() ) {
			$sysconfig[$cfg['name']] = $cfg['value'];
		}

		return $sysconfig;
	}

	function refreshSession($id) {

		self::$sysconfig = self::refreshSysConfig();

		// can't use prog, coz the sys might not been init (from session)
		$user = db::fetch('SELECT * FROM user WHERE id=?', array($id));

		if(!$user) return;
	}

	function reset()	// similar to user logout
	{
		// reserve info
		$view = $_SESSION['view'];

		// reset session
		$_SESSION = array();

		$_SESSION['view']			 = $view;
		$_SESSION['_sess_user_role'] = _ROLE::DEFAULT_ROLE;
		$_SESSION['auth'] = uniqid('', TRUE);

		cookie::set('account', '', time()-3600*24*30);
		cookie::set('token',   '', time()-3600*24*30);
	}

	public static function save($id, $data) {
        //save data 2 database
        if ($id != self::$id && !Privilege::role(_ROLE::SYSOP)) {
            return false;
        }

        $qry    = array();
        $value  = array();

        foreach ($data as $n => $v) {
            if ($n == 'id' || $n == 'account') continue;

            $qry[]   = "{$n}=?";
            $value[] = $v;
        }

        $qrystr = join(', ', $qry);

        db::query("UPDATE user SET {$qrystr} WHERE id='" . $id . "'", $value);

		if ($id == self::$id) self::setSession($id);

        return true;
    }

    public static function _init() {

        self::$id = (empty($_SESSION['_sess_user_id'])) ? 0 : $_SESSION['_sess_user_id'];

		$account = cookie::get('account', 'string');
		$token	 = cookie::get('token',   'string');

		if (self::$id == 0 && ($account != '' && $token != '')) {

			$user = db::fetch('SELECT * FROM user WHERE account=?', array($account));

			if ($token == md5("{$user['account']}|{$user['password']}")) {
				self::setSession($user['id']);
				return;
			}
		}

		// if refresh has done!
		if (!empty(self::$role))		$_SESSION['_sess_user_role']	= self::$role;
		if (!empty(self::$roleAry))		$_SESSION['_sess_user_roleAry'] = self::$roleAry;
		if (!empty(self::$sysconfig))	$_SESSION['_sess_sysconfig']	= self::$sysconfig;

        self::$account  = (empty($_SESSION['_sess_user_account']))  ? '' : $_SESSION['_sess_user_account'];
        self::$name     = (empty($_SESSION['_sess_user_name']))     ? '' : $_SESSION['_sess_user_name'];
        self::$email    = (empty($_SESSION['_sess_user_email']))    ? '' : $_SESSION['_sess_user_email'];
        self::$phone    = (empty($_SESSION['_sess_user_phone']))    ? '' : $_SESSION['_sess_user_phone'];

		self::$role     = (empty($_SESSION['_sess_user_role']))     ? _ROLE::DEFAULT_ROLE  : $_SESSION['_sess_user_role'];
        self::$roleAry  = (empty($_SESSION['_sess_user_roleAry']))  ? array(_ROLE::DEFAULT_ROLE) : $_SESSION['_sess_user_roleAry'];

		// default locale zh-tw

		if (empty($_SESSION['_sess_sysconfig'])) {
			self::$sysconfig = $_SESSION['_sess_sysconfig'] = self::refreshSysConfig();
		} else {
			self::$sysconfig = $_SESSION['_sess_sysconfig'];
		}
		
		if (!$_SESSION['_sess_HTTP_SERVER_VARS']) {
			$_SESSION['_sess_HTTP_SERVER_VARS'] = $_SERVER;
		}
    }
}