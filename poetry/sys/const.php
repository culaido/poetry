<?php


Class _UPLOAD_COMPLETE {
	const notComplete	= 0;
	const complete		= 1;
}

Class _SECRET_KEY {
	const forgotPwd = 'T8isUo4363r223E36q86k4138uO6CEF71Z24q6SV9lH4917Q79261hmiI75brW5T';
}

class _PAGE {
	const index	= 'index';

	// lv1
	const user	= 'user';

	// lv2
	const folder = 'folder';

	// lv3
	const content = 'media';

}

// SYSTEM =============================================
define('WEB_ROOT', '/' . WEB_ACCOUNT);
define('COOKIE_DOMAIN',	'');

define('HTTP_HOST',       $_SERVER['HTTP_HOST']);
define('SYS_PATH',        'sys');
define('CORE_PATH',       SYS_PATH . '/core');              // 核心元件路徑
define('PAGE_PATH',       SYS_PATH . '/pages');             // 元件路徑
define('MODULE_PATH',     SYS_PATH . '/modules');			// 模組路徑
define('TEMPLATE_PATH',   SYS_PATH . '/templates');         // 樣板路徑
define('LOCALE_PATH',	  SYS_PATH . '/res/locale');		// 語言擋路徑
define('CSS_PATH',   	  WEB_ROOT . '/' . SYS_PATH . '/style'); // 樣板CSS路徑

define('SITE_PATH',   	  WEB_ROOT . '/site/'); // 樣板CSS路徑


define('LIB_PATH',        SYS_PATH . '/libs');              // PHP LIB 路徑
define('CLASS_PATH',      SYS_PATH . '/libs/class');        // PHP CLASS 路徑
define('RES_PATH',		  SYS_PATH . '/res');

define('JS_PATH',         WEB_ROOT . '/' . SYS_PATH . '/js');          // JS 路徑

/* add '/' is because of img root path is different from php */
define('ICON_PATH',       WEB_ROOT . '/' . SYS_PATH . '/res/icon');          // 系統用 icon

// SYSDATA =============================================
define('SYSDATA_PATH',            'sysdata');					// sysdata 路徑
define('SYSDATA_CACHE_PATH',      SYSDATA_PATH.'/cache');		// sysdata/cache 路徑
define('SYSDATA_USER_PATH',       SYSDATA_PATH.'/users');       // sysdata/users 路徑
define('SYSDATA_SYS_CACHE_PATH',  SYSDATA_PATH.'/syscache');    // 快取路徑
define('SYSDATA_TEMP_PATH',		  SYSDATA_PATH.'/temp');
define('SYS_LOG',                 SYSDATA_PATH.'/log');		    // 樣板路徑

// ADM =================================================
define('ADMIN_PATH', 'adm');
define('ADMIN_PAGE_PATH', SYS_PATH.'/'.ADMIN_PATH);

define('SESS_LIFE', 14400);
