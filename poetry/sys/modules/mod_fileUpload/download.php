<?php
	$rel_path = dirname($_SERVER['SCRIPT_FILENAME']);
	$dirs = explode('/', $rel_path);

	array_pop($dirs);
	array_pop($dirs);
	array_pop($dirs);

	$rel_path = join('/', $dirs) . '/';

	require_once $rel_path . 'config.php';

	require_once $rel_path . CORE_PATH  . '/sys.php';
    require_once $rel_path . CORE_PATH  . '/lib.php';


	require_once $rel_path . CORE_PATH	. '/privilege.php';

	require_once $rel_path . MODULE_PATH . '/mod_fileUpload/mod_fileUpload_helper.php';

    ignore_user_abort(true);
    global $m;

	_init();
    //session_start();
    profile::_init();

    $id  = args::get('id', 'int');

    if (!$id) { echo $m['attachNotExist']; exit; }

	$key = args::get('key', 'nothing');

	$ret = mod_fileUpload_helper::checkKey($id, $key);

	if ($ret == FALSE) {
        echo $m['attachNotExist'];
        exit;
	}

    $attach = db::fetch('SELECT path, srcName, name FROM mod_attach_item WHERE id=?', array($id));

    if (!$attach){
        echo $m['attachNotExist'];
        exit;
    }

    $path  = $rel_path . $attach['path'];

    $fp    = fopen($path, 'rb');
    $fsize = filesize($path);

    if (isset($_SERVER['HTTP_RANGE']) && ($_SERVER['HTTP_RANGE'] != "") && preg_match("/^bytes=([0-9]+)-$/i", $_SERVER['HTTP_RANGE'], $match) && ($match[1] < $fsize))
        $start = $match[1];
    else
        $start = 0;

    @Header('Cache-control: public');
    @Header('Pragma: public');

    if ($start > 0)
    {
        fseek($fp, $start);
        Header('HTTP/1.1 206 Partial Content');
        Header("Content-Length: " . ($fsize - $start));
        Header("Content-Ranges: bytes" . $start . "-" . ($fsize - 1) . "/" . $fsize);
    }
    else
    {
        Header("Content-Length: {$fsize}");
        Header("Accept-Ranges: bytes");
    }
    $memi = isImg($attach['srcName']);
    if ($memi)
    {
        @Header("Content-Type: {$memi}");
    }
    else
    {
        @Header('Content-Type: application/octet-stream; charset=utf-8');
		@Header("Content-Disposition: attachment; filename=" . str_replace( ' ', '_', $attach['srcName'] ) . ";");
    }
    fpassthru($fp);

    function isImg($fileName) {
        $ext = lib::ext($fileName);
        switch (strtoupper($ext)) {
            case 'PNG'  : return 'image/png';  break;
            case 'JPG'  : return 'image/jpg';  break;
            case 'JPEG' : return 'image/jpeg'; break;
            case 'GIF'  : return 'image/gif';  break;
            case 'BMP'  : return 'image/bmp';  break;

            default : return FALSE; break;
        }
    }