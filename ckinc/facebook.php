<?php
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/secret.php");
require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/facebook/autoload.php");
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;	
	

//###########################################################################
//#
//###########################################################################
class cFacebook_ServerSide{
	const FB_USER_FOLDER = "[facebook]";
	const FB_SESS_USER = "fbuser";
	const FB_SESS_USERID = "fbuserid";
	
	//*******************************************************************
	public static function is_facebook(){
		//force on query string
		if (isset($_GET["fb"])) return true;
		
		//user agent is like facebookexternalhit/1.1
		return preg_match("/facebook/", strtolower ($_SERVER['HTTP_USER_AGENT']));
	}
	
	
	//*******************************************************************
	private static function pr_setSessionUser($poGraphObject){
		$sFirstName = $poGraphObject->getProperty("first_name");
		$sLastName = $poGraphObject->getProperty("last_name");
		$sID = $poGraphObject->getProperty("id");
		$sUser = "$sFirstName $sLastName";
		$_SESSION[self::FB_SESS_USER] = $sUser;
		$_SESSION[self::FB_SESS_USERID] = $sID;
		return $sUser;
	}
	
	//*******************************************************************
	public static function getSessionUser(){
		//get the user from the session
		if (isset($_SESSION[self::FB_SESS_USER]))			
			return $_SESSION[self::FB_SESS_USER];
		else{
			cDebug::write("no facebook session user");
			return null;
		}
	}
	
	//*******************************************************************
	public static function getSessionUserID(){
		//get the user from the session
		if (isset($_SESSION[self::FB_SESS_USERID]))			
			return $_SESSION[self::FB_SESS_USERID];
		else{
			cDebug::write("no facebook session userid");
			return null;
		}
	}
	
	//*******************************************************************
	public static function getStoredUser($psUserID){
	
		$sUser = self::getSessionUser();
				
		//is this user details stored?
		if (!$sUser){
			cDebug::write("username not in session, checking if known");
		
			
			$oGraphObject = cObjStore::get_file(self::FB_USER_FOLDER, $psUserID);
			if ($oGraphObject){
				cDebug::write("found stored user");
				//$aNames = $oGraphObject->getPropertyNames();
				$sUser = self::pr_setSessionUser($oGraphObject);
			}
		}
		
		return $sUser;
	}
	
	//*******************************************************************
	private static function pr_storeUserDetails($psUserID, $poData){
		//store the details
		cObjStore::put_file(self::FB_USER_FOLDER,$psUserID, $poData);
		
		//add to list of FB users (using hash to obfuscate for security)
		$aFBApp = self::getAppID();
		$sAllUsersFile = "AllFBusers".$aFBApp["S"];
		$aFBUsers = cHash::get($sAllUsersFile);
		if (!$aFBUsers) $aFBUsers = [];
		$aFBUsers[$psUserID] = 1;
		cHash::put($sAllUsersFile, $aFBUsers, true);
	}
	
	//*******************************************************************
	public static function getAppID(){
		if (cHeader::is_localhost()){
			cDebug::write("using development credentials for localhost");
			return ["I"=>cSecret::$FB_DEV_APP, "S"=>cSecret::$FB_DEV_SECRET];
		}else
			return  ["I"=>cSecret::$FB_APP, "S"=>cSecret::$FB_SECRET];
	}
	
	//*******************************************************************
	public static function getUserIDDetails($psUserID, $psToken){
		$aFBApp = self::getAppID();
		FacebookSession::setDefaultApplication($aFBApp["I"], $aFBApp["S"]);
		cDebug::write("FBAPP: ".$aFBApp["I"]);
				
		$oSession = new FacebookSession($psToken);
		try {
			$oSession->validate();
		} catch (Exception $ex) {
			cDebug::error("failed FB session: ". $ex->getMessage());
		}
		cDebug::write("FB session OK");
		
		//get the details of the user from facebook 
		$oFBRequest = new FacebookRequest( $oSession, 'GET', '/me');
		$oFBResponse = $oFBRequest->execute();
		$oGraphObject = $oFBResponse->getGraphObject();
		//cDebug::vardump($oGraphObject);
		
		//remember this user
		$sUser = self::pr_setSessionUser($oGraphObject);
		self::pr_storeUserDetails( $psUserID, $oGraphObject);
		
		return $sUser;
	}
}
?>