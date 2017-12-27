<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$phpinc/ckinc/header.php");
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/appdynamics/common.php");
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/common.php");

//#################################################################
//# 
//#################################################################
//##################################################################
class cLogin{
	const KEY_HOST = "h";
	const KEY_ACCOUNT = "a";
	const KEY_USERNAME = "u";
	const KEY_PASSWORD = "p";
	const KEY_HTTPS = "ss";
	const KEY_REFERRER = "r";
	const KEY_SUBMIT = "s";
}

class cAppDynCredentials{
	const HOST_KEY = "apple";
	const ACCOUNT_KEY = "pear";
	const USERNAME_KEY = "orange";
	const PASSWORD_KEY = "lemon";
	const USE_HTTPS_KEY = "quince";
	const PROXY_KEY = "melon";
	const RESTRICTED_LOGIN_KEY = "basil";
	const PROXY_PORT_KEY = "grape";
	const PROXY_CRED_KEY = "diet";
	const LOGGEDIN_KEY = "log";
	
	const DEMO_USER = "demo";
	const DEMO_PASS = "d3m0";
	const DEMO_ACCOUNT = "demo";
	
	public $account;
	public $host;
	public $username;
	public $password;
	public $use_https;
	public $restricted_login = null;
	private $mbLogged_in = false;
	
	//**************************************************************************************
	function load(){
		$sAAcc = cCommon::get_session(self::ACCOUNT_KEY);
		if(!$sAAcc) cDebug::error("Couldnt get account from session");
		$this->account = $sAAcc;

		$sADUsername = cCommon::get_session(self::USERNAME_KEY);
		if(!$sADUsername ) cDebug::error("Couldnt get username from session");
		$this->username = $sADUsername;
		
		$sADPass = cCommon::get_session(self::PASSWORD_KEY);
		if(!$sADPass) cDebug::error("Couldnt get password from session");
		$this->password = $sADPass;

		if ($this->is_demo()){
			$this->host = "Demo";
		}else{
			$sHost = cCommon::get_session(self::HOST_KEY);
			if(!$sHost) cDebug::error("Couldnt get host from session");
			$this->host = $sHost;
			
			$bUse_https = cCommon::get_session(self::USE_HTTPS_KEY);
			$this->use_https = $bUse_https;
		}

		$this->restricted_login = cCommon::get_session(self::RESTRICTED_LOGIN_KEY);
		$this->mbLogged_in = cCommon::get_session(self::LOGGEDIN_KEY);  
		//TODO should we really trust this, should be more secure than just a session variable
	}
	
	//**************************************************************************************
	function load_from_header(){
		$this->host = cHeader::get(cLogin::KEY_HOST);
		$this->account  = cHeader::get(cLogin::KEY_ACCOUNT);
		$this->username  = cHeader::get(cLogin::KEY_USERNAME);
		$this->password  = cHeader::get(cLogin::KEY_PASSWORD);
		
		$sUse_https = cHeader::get(cLogin::KEY_HTTPS);
		
		$this->use_https = ($sUse_https=="yes");		
		
		$this->save();		//populate the session
	}
	
	//**************************************************************************************
	//this performs the login
	public function save(){
		cDebug::write("saving");
		$_SESSION[self::HOST_KEY]  = $this->host;
		$_SESSION[self::ACCOUNT_KEY]  = $this->account;
		$_SESSION[self::USERNAME_KEY]  = $this->username;
		$_SESSION[self::PASSWORD_KEY]  = $this->password;  //TODO should be encrypted
		$_SESSION[self::USE_HTTPS_KEY]  = $this->use_https;
		$_SESSION[self::RESTRICTED_LOGIN_KEY]  = $this->restricted_login;
		
		//get something simple if it works you're logged in
		$oResponse = cAppDyn::GET_Applications();
		cDebug::write("logged in");
		$_SESSION[self::LOGGEDIN_KEY] = true;
		$this->mbLogged_in = true;
	}

	//**************************************************************************************
	public function logged_in(){
		if (!$this->mbLogged_in)
			cDebug::error("not logged in");
			
		if ($this->restricted_login)
			if (!cHttp::page_matches($this->restricted_login))
				cDebug::error("restricted login");
		return true;
	}
	
	//**************************************************************************************
	public function encode(){
		return urlencode(urlencode($this->username)."@".$this->account);
	}

	function __construct() {
       //$this->load();
	}
	
	//**************************************************************************************
	public function is_demo(){
		if ($this->account == cAppDynCredentials::DEMO_ACCOUNT){
			if (($this->username == cAppDynCredentials::DEMO_USER) && ($this->password == cAppDynCredentials::DEMO_PASS)){
				return true;
			}else{
				cDebug::error("wrong demo login details");
			}
		}
		return false;
	}
	
	//**************************************************************************************
	//* STATICS
	//**************************************************************************************	
	public static function clear_session(){
		@session_destroy ();
		session_start ();
	}
	
	//**************************************************************************************
	public static function get_login_token(){
		cDebug::ENTER();
		
		//------------- check login credentials --------------------------
		$oCred = new cAppDynCredentials;
		$oCred->load();
		if (!$oCred->logged_in()) cDebug::error("must be logged in");
		if ($oCred->restricted_login) cDebug::error("token not available in restricted login");
		
		//------------- generate the token --------------------------------
		$sKey = cCommon::my_IP_address(). $oCred->host.$oCred->account.$oCred->username;
		$sHash = cHash::hash($sKey);
		cDebug::write("Key is $sKey, hash is $sHash");
		cHash::put_obj($sHash, $oCred, true );
			
		return $sHash;
	}
	
	//**************************************************************************************
	public static function login_with_token($psToken ){
		$oCred = cHash::get_obj($psToken);
		if ($oCred == null) cDebug::error("token not found");
		if (get_class($oCred) !== "cAppDynCredentials") cDebug::error("unexpected class");

		//perform the login
		$oCred->save();
	}
	

}
?>