<?php

Class lib {


	public static function timeSpan($date)
	{
		global $m;

		$limit = self::timeDiff(date('Y/m/d H:i:s'), $date);

		if ($limit < 60)						return sprintf(_T('beforeSec'),  1);
		if ($limit >= 60	&& $limit < 3600)	return sprintf(_T('beforeMin'),  floor($limit/60));
		if ($limit >= 3600	&& $limit < 86400)	return sprintf(_T('beforeHour'), floor($limit/3600));
		if ($limit >= 86400	&& $limit < 604800) return sprintf(_T('beforeDay'),  floor($limit/86400));

		return self::simplifyTimeSpan($date);
	}

	// if same year return m-d, otherwise return Y-m
	public static function simplifyTimeSpan($date) {
		return (date("Y") == self::dateFormat($date, 'Y'))
				? self::dateFormat($date, 'm-d')
				: self::dateFormat($date, 'Y-m-d');
	}

    public static function timeDiff($lastTime, $firstTime) {
        $firstTime  = strtotime($firstTime);

        $lastTime   = strtotime($lastTime);
        $timeDiff   = $lastTime - $firstTime;

        return $timeDiff;
    }

    public static function ext($name)
    {
    	error_reporting(0);
        $pos = strrpos($name, ".");

        if ($pos === false) return "";
        else return strtolower(substr($name, $pos+1));
    }

	public static function htmlPurifier($html, $options = array())
	{
		// if only white space
		if (trim(strip_tags($html, _RESERVED_STRIP_TAGS_)) == '') return '';


		require_once (CLASS_PATH . '/htmlpurifier/HTMLPurifier.auto.php');
		$config = HTMLPurifier_Config::createDefault();
		$defaultWhiteList = array("[\s\S]*.camdemy.com", "www.youtube.com/embed/", "player.vimeo.com/video/", "embed.ted.com");
		if (!in_array(REAL_HOSTNAME, $defaultWhiteList)) {
			$defaultWhiteList[] = REAL_HOSTNAME;
		}
		$iFrameRegexp = join("|", $defaultWhiteList);

		// configuration goes here:
		$config->set('Core.Encoding', 			'UTF-8'); // replace with your encoding
		$config->set('HTML.Doctype', 			'XHTML 1.0 Transitional'); // replace with your doctype
		$config->set('HTML.Attr.Name.UseCDATA', TRUE);
		$config->set('HTML.SafeEmbed',			TRUE);
		$config->set('HTML.SafeObject', 		TRUE);
		$config->set('HTML.SafeIframe', 		TRUE);
		$config->set('URI.SafeIframeRegexp', 	"%//({$iFrameRegexp})%");

		// for debug
		//$config->set('Core.CollectErrors', 	true);

		// code hightlighter escape hyperlink
		$out = array();

		if (!preg_match_all("/<pre [\s\S]*(http|https|ftp):\/\/[\s\S]*<\/pre>/i", $html, $out)) {
			$config->set('AutoFormat.Linkify', TRUE);
		}

        $config->set('Attr.AllowedRel', array('name', 'rel'));

		// add allowable attribute
		$def = $config->getHTMLDefinition(TRUE);
		$def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top,_popup');
		$def->addAttribute('a', 'rel',    'CDATA');
		$def->addAttribute('a', 'value',  'CDATA');

		$def->addAttribute('img', 'data-mathml',  'CDATA');

		$purifier = new HTMLPurifier($config);

		// for debug
		//echo $purifier->context->get( 'ErrorCollector' )->getHTMLFormatted( $config );
		return $purifier->purify(trim($html));
	}
    public static function dateFormat($date, $format='Y-m-d') {
		return ( strtotime($date)<=0 ) ? '' : date_format(date_create($date), $format);
	}

    public static function filelog($text) {
		$caller = array_shift(debug_backtrace());

		$info = "file: {$caller['file']} line: {$caller['line']}";

		if (is_array($text) || is_object($text)) $text = print_r($text, TRUE);
        $fp = fopen(ROOT_PATH . "/log.txt", "a");
        fwrite($fp, "[{$info}] " . date("Y-m-d H:i:s") . ":\n {$text}\n");
        fclose($fp);
    }


	public static function checkEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
	}


	public static function makeAuth($key) {
		$_SESSION['auth'] = ($_SESSION['auth']=='')?uniqid('',TRUE):$_SESSION['auth'];
		$str = (is_array($key) ? join('|', $key) : $key) . '|' . $_SESSION['auth'];
		return md5($str);
	}

	public static function ajaxAuth( $ajaxUrl, $vals) {
		return self::makeAuth( array_merge( (array)$ajaxUrl, (array)$vals) );
	}

	public static function lockUrl($url, $vals = array()) {

		if (!is_array($vals)) {
			sys::exception('lib::lockUrl(url, vals) - vals MUST be an array');
		}

		if (substr($url, -1) !== '/') $url .= '/';

		$qry			 = $vals;
		if (count($vals)>0) $qry['_lock'] = join(',', array_keys($vals) );
		$qry['ajaxAuth'] = self::ajaxAuth($url, $vals);

		$concatStr = ( strpos($url, '?') === FALSE) ? "?" : "&";
		// sys::callstack();
		return $url . $concatStr . http_build_query($qry);
	}



    public static function htmlChars($str, $flag=ENT_QUOTES) { return htmlspecialchars($str, $flag); }

	public static function parsePageId($pageId)	{
		$arr = preg_split('/\./', $pageId);

		$item[0] = array_shift($arr);
		$item[1] = join('.', $arr);

		return $item;
	}

	public static function detectIEversion() {
		preg_match('/MSIE ([0-9].[0-9])/', $_SERVER['HTTP_USER_AGENT'],$reg);
		return (!isset($reg[1])) ? -1 : floatval($reg[1]);
    }

	public static function mail($toEMail, $subject, $message, $headers = ''){

		try {

			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
			$headers .= 'From: ' . SYS_MAIL_FROM . "\r\n";

			@ mail($toEMail, $subject, $message, $headers);
		  return true;
		} catch (phpmailerException $e) {
			// echo $e->errorMessage(); //Pretty error messages from PHPMailer
		  return FALSE;
		} catch (Exception $e) {
			// echo $e->getMessage(); //Boring error messages from anything else!
		  return FALSE;
		}
	}
}


function load_tpl($tpl = '') {

	if ($tpl == '') return;

	$t = prog::get('mod_template');
	$t->load_tpl($tpl);
}


function _T($id, $replace = NULL) {
	global $m;

	$str = ($m[$id]) ? $m[$id] : $id;

	return ($replace) ? strtr($str, $replace) : $str;
}