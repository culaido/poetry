<?php
define("WEB_ACCOUNT", 'poetry');
define('ROOT_PATH', dirname(__FILE__) );

// Load system config
require_once ROOT_PATH . '/sys/config.php';

// Database settings

define("DB_DSN", 'mysql:host=localhost;dbname=poetry;');
define("DB_USR", 'root');
define("DB_PWD", '');

/*
define("DB_DSN", 'mysql:host=localhost;dbname=vhost77516;');
define("DB_USR", 'vhost77516');
define("DB_PWD", 'vv456789');
*/

// Cookie settings
define('COOKIE_DOMAIN', '');

define("SYS_MAIL_FROM", 'poetry.no-reply@mail.com');

define('SESS_LIFE', 8 * 60 * 60);

define("EMBEDLY_KEY", '6d0528da844a4d3f85c27264d0f2c871');
define("GOOGLE_ANALYTICS", 'UA-45386651-2');
define("USER_VOICE", '239376');

Class _ROLE { 
	const DEFAULT_ROLE		= 5; // member
	const SYSOP				= 1; // SYSOP
}


