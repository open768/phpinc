<?php
require_once("$phpinc/ckinc/debug.php");

class cSession{
	public static $THROW_ON_SESSION_ERROR = false;
	public static $started_session = false;
	const SESSION_FOLDER = "[sessions]";
	
	//*******************************************************************
	public static function start(){
		global $root;
		
		if (!self::$started_session){
			if (session_status() == PHP_SESSION_NONE){ 	
				if (!headers_sent()){	//this is vitally important - any header work must precede any html being sent
					//--- change the location where session data is stored
					$sFolder = "$root/".self::SESSION_FOLDER;
					if (!file_exists($sFolder))	mkdir($sFolder, 0700, true);
					session_save_path($sFolder);
					
					//--- start your sessions
					if (!session_start())
						cDebug::error("cant start session - no idea why");
					else
						self::$started_session=true;
				}else
					if (self::$THROW_ON_SESSION_ERROR)
						cDebug::error("cant start session - headers sent allready");
					else
						cDebug::write("cant start session - headers sent allready");
			}else
				cDebug::write("Session allready there");
		}
	}
	
	//*******************************************************************
	public static function set($psName, $psValue){
		global $_SESSION;
		$_SESSION[$psName] = $psValue;
	}
	
	//*******************************************************************
	public static function get($psName){
		global $_SESSION;
		$sValue = null;
		if (isset ($_SESSION[$psName]))	$sValue = $_SESSION[$psName];
		
		return $sValue;
	}
	//*******************************************************************
	public static function info(){
		if (session_status() == PHP_SESSION_NONE)
			cDebug::error("no session");
		else{
			cDebug::write("session contents");
			cDebug::vardump($_SESSION);
		}
	}
}
?>