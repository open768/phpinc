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
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/appdynamics/common.php");
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/common.php");

//#################################################################
//# 
//#################################################################
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
	
	public $account;
	public $host;
	public $username;
	public $password;
	public $use_https;
	public $restricted_login = null;
	private $mbLogged_in = false;
	
	function load(){
		$sAAcc = cCommon::get_session(self::ACCOUNT_KEY);
		if(!$sAAcc) cDebug::error("Couldnt get account from session");
		$this->account = $sAAcc;

		$sHost = cCommon::get_session(self::HOST_KEY);
		if(!$sHost) cDebug::error("Couldnt get host from session");
		$this->host = $sHost;
		
		$sADUsername = cCommon::get_session(self::USERNAME_KEY);
		if(!$sADUsername ) cDebug::error("Couldnt get username from session");
		$this->username = $sADUsername;
		
		$sADPass = cCommon::get_session(self::PASSWORD_KEY);
		if(!$sADPass) cDebug::error("Couldnt get password from session");
		$this->password = $sADPass;

		$bUse_https = cCommon::get_session(self::USE_HTTPS_KEY);
		$this->use_https = $bUse_https;

		$this->restricted_login = cCommon::get_session(self::RESTRICTED_LOGIN_KEY);
		$this->mbLogged_in = cCommon::get_session(self::LOGGEDIN_KEY);
}
	
	public function save(){
		cDebug::write("saving");
		$_SESSION[self::HOST_KEY]  = $this->host;
		$_SESSION[self::ACCOUNT_KEY]  = $this->account;
		$_SESSION[self::USERNAME_KEY]  = $this->username;
		$_SESSION[self::PASSWORD_KEY]  = $this->password;
		$_SESSION[self::USE_HTTPS_KEY]  = $this->use_https;
		$_SESSION[self::RESTRICTED_LOGIN_KEY]  = $this->restricted_login;
		
		//get something simple if it works - logged in
		$oResponse = cAppDyn::GET_Applications();
		cDebug::write("logged in");
		$_SESSION[self::LOGGEDIN_KEY] = true;
	}

	public function logged_in(){
		if (!$this->mbLogged_in)
			cDebug::error("not logged in");
			
		if ($this->restricted_login)
			if (!cHttp::page_matches($this->restricted_login))
				cDebug::error("restricted login");
		return true;
	}

	public static function clear_session(){
		@session_destroy ();
		session_start ();
	}
}
?>