<?php

require_once 'const.php';

if ( substr(PHP_OS, 0, 3) === 'WIN')
	define('PLATFORM',   'win');
else
	define('PLATFORM',   'unix');