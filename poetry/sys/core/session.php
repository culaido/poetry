<?php
class session {

	private $client_ip;
	private $proxy_ip;
	private static $sess_life;
	private static $_init;

	public function __construct($sess_life = 14400, $useDB = TRUE) {

		if (self::$_init) return;

		self::$_init = TRUE;
		self::$sess_life = $sess_life;

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->client_ip	= $_SERVER['HTTP_X_FORWARDED_FOR'];
			$this->proxy_ip		= $_SERVER['REMOTE_ADDR'];
		}
		else {
			$this->client_ip	= $_SERVER['REMOTE_ADDR'];
			$this->proxy_ip		= "";
		}

		if ($useDB) {
			session_set_save_handler(
				array($this,'sess_open'),
				array($this,'sess_close'),
				array($this,'sess_read'),
				array($this,'sess_write'),
				array($this,'sess_destroy'),
				array($this,'sess_gc')
			);
		}

		session_start();
	}

	function sess_open($save_path, $session_name)
	{ return true; }

	function sess_close()
	{ return true; }


	function sess_read($key)
	{
		$obj = db::fetch("SELECT * FROM session WHERE sesskey=:sesskey",
			array(':sesskey'=>$key), FALSE );

		if ($obj) {

			if ($obj['_refresh']==1) {
				profile::refreshSession( $obj['_userID'] );
				db::query("UPDATE session SET _refresh=0 WHERE sesskey=?", array($key), FALSE);
			}

			return $obj['vvalue'];

		} else {

			$expiry = time() + self::$sess_life;

			db::query("INSERT INTO session
								SET sesskey=:sessKey,
									clientIP=:clientIP,
									proxyIP=:proxyIP,
									expiry=:expiry",
				array(':sessKey'=>$key, ':clientIP'=>$this->client_ip, ':proxyIP'=>$this->proxy_ip, ':expiry'=>$expiry), FALSE
				);
			return '';
		}
	}

	function sess_write($key, $val)
	{
		$expiry = time() + self::$sess_life;

		$affected = db::query('UPDATE session SET expiry=:expiry, vvalue=:vvalue, _userID=:userID, _cnt=_cnt+1 WHERE sessKey=:sesskey',
				array(':expiry'=>$expiry, ':vvalue'=>$val, ':sesskey'=>$key, ':userID'=>profile::$id), FALSE );

		return TRUE;
	}


	function sess_destroy($key)
	{
		return db::query("DELETE FROM session WHERE sesskey=?", array($key), FALSE);
	}

	function sess_gc($maxlifetime = null)
	{
		return db::query("DELETE FROM session WHERE expiry < ?", array(time()), FALSE);
	}

	public function __destruct(){
		session_write_close();
	}



	public static function kick($key) {
		db::query('DELETE FROM session WHERE sesskey=?', array($key), FALSE);
	}

	public static function get_sess_life() {
		return self::$sess_life;
	}


	public static function refresh($userID = NULL) {
		if ($userID==NULL) $userID = profile::$id;
		if (!is_array($userID)) $userID = array($userID);
		db::query('UPDATE session SET _refresh=1 WHERE FIND_IN_SET(_userID, :userID)', array(':userID'=> join(',', $userID)), FALSE);
	}

	private static $_logged_count;
	public static function logged_count() {

		if (!self::$_logged_count) {
			$obj = db::fetch("SELECT COUNT(*) as CNT FROM session WHERE _userID!=0 AND expiry>=?", array(time()), FALSE);
			self::$_logged_count = $obj['CNT'];
		}

		return self::$_logged_count;
	}

	private static $_visitor_count;
	public static function visitor_count() {

		if (!self::$_visitor_count) {
			$obj = db::fetch("SELECT COUNT(*) as CNT FROM session WHERE _userID=0 AND expiry>=? AND _cnt>0", array(time()), FALSE);
			self::$_visitor_count = $obj['CNT'];
		}

		return self::$_visitor_count;
	}

	public static function refreshAll() {
		db::query('UPDATE session SET _refresh=1', array(), FALSE);
	}

	public static function getOnlineList() {

		$rs = db::select("SELECT * FROM session WHERE _userID!=0 AND expiry>=? ORDER BY expiry DESC", array(time()), FALSE);

		$data = array();
		while ($obj = $rs->fetch()) {

			$obj['vvalue'] = self::unserializeSession($obj['vvalue']);
			$data[] = $obj;

		}

		return $data;

	}

	public static function unserializeSession( $data )
	{
		if(  strlen( $data) == 0)
		{
			return array();
		}

		// match all the session keys and offsets
		preg_match_all('/(^|;|\})([a-zA-Z0-9_]+)\|/i', $data, $matchesarray, PREG_OFFSET_CAPTURE);

		$returnArray = array();

		$lastOffset = null;
		$currentKey = '';
		foreach ( $matchesarray[2] as $value )
		{
			$offset = $value[1];
			if(!is_null( $lastOffset))
			{
				$valueText = substr($data, $lastOffset, $offset - $lastOffset );
				$returnArray[$currentKey] = unserialize($valueText);
			}
			$currentKey = $value[0];

			$lastOffset = $offset + strlen( $currentKey )+1;
		}

		$valueText = substr($data, $lastOffset );
		$returnArray[$currentKey] = unserialize($valueText);

		return $returnArray;
	}

}