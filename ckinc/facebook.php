<?php
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/secret.php");
require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/facebook/autoload.php");
require_once("$phpinc/ckinc/objstoredb.php");
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;	
	

//###########################################################################
//#
//###########################################################################
class cFacebook_ServerSide{
	const FB_USER_FOLDER = "[facebook]";
	const FB_ALL_USERS= "[all users]";
	const FB_SESS_USER = "fbuser";
	const FB_SESS_USERID = "fbuserid";
	static $oObjStore = null;
	
	static function pr_init_objstore(){
		cDebug::enter();
		if (self::$oObjStore == null){
			$oStore = new cObjStoreDB();
			$oStore->realm = "FB";
			//$oStore->SHOW_SQL = true; //DEBUG
			$oStore->set_table("FB");	//possibly auth is a reserved name
			self::$oObjStore = $oStore;
		}
		cDebug::leave();
	}
	//*******************************************************************
	public static function is_facebook(){
		//force on query string
		if (isset($_GET["fb"])) return true;
		
		//user agent is like facebookexternalhit/1.1
		return preg_match("/facebook/", strtolower ($_SERVER['HTTP_USER_AGENT']));
	}
	
	
	//*******************************************************************
	private static function pr_setSessionUser($poGraphObject){
		cDebug::enter();
		$sUser = $poGraphObject->getProperty("name");
		$sID = $poGraphObject->getProperty("id");
		$_SESSION[self::FB_SESS_USER] = $sUser;
		$_SESSION[self::FB_SESS_USERID] = $sID;
		cDebug::leave();
		return $sUser;
	}
	
	//*******************************************************************
	public static function getSessionUser(){
		cDebug::enter();
		$sUser = null;
		//get the user from the session
		if (isset($_SESSION[self::FB_SESS_USER]))	
			if (! cDebug::$IGNORE_CACHE){
				cDebug::extra_debug("using session user");
				$sUser = $_SESSION[self::FB_SESS_USER];
			}else
				cDebug::extra_debug("ignoring session user");
		else
			cDebug::write("no facebook session user");
		
		cDebug::leave();
		return $sUser;
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
		cDebug::enter();
		
		if (cDebug::$IGNORE_CACHE)
			cDebug::extra_debug("nocache - not looking here");
		else{
			$sUser = self::getSessionUser();
					
			//is this user details stored?
			if (!$sUser){
				cDebug::write("username not in session, checking if known");
			
				$oGraphObject = self::$oObjStore->get_oldstyle(self::FB_USER_FOLDER, $psUserID);
				if ($oGraphObject){
					cDebug::write("found stored user");
					//$aNames = $oGraphObject->getPropertyNames();
					$sUser = self::pr_setSessionUser($oGraphObject);
				}
			}
		}
		
		cDebug::leave();
		return $sUser;
	}
	
	//*******************************************************************
	private static function pr_storeUserDetails($psUserID, $poData){
		cDebug::enter();
		//store the details
		self::$oObjStore->put_oldstyle(self::FB_USER_FOLDER,$psUserID, $poData);
		
		//add to list of FB users
		$aFBUsers = self::$oObjStore->get_oldstyle(self::FB_USER_FOLDER, self::FB_ALL_USERS);
		if (!$aFBUsers) $aFBUsers = [];
		$aFBUsers[$psUserID] = 1;
		self::$oObjStore->put_oldstyle(self::FB_USER_FOLDER, self::FB_ALL_USERS, $aFBUsers);

		cDebug::leave();
	}
	
	//*******************************************************************
	public static function getAppID(){
		$sID = null;
		cDebug::enter();
		if (cHeader::is_localhost()){
			cDebug::write("using development credentials for localhost");
			$sID = ["I"=>cSecret::$FB_DEV_APP, "S"=>cSecret::$FB_DEV_SECRET];
		}else
			$sID =   ["I"=>cSecret::$FB_APP, "S"=>cSecret::$FB_SECRET];
		cDebug::leave();
		return $sID;
	}
	
	//*******************************************************************
	public static function getUserIDDetails($psUserID, $psToken){
		cDebug::enter();
		$aFBApp = self::getAppID();

		cDebug::extra_debug("-- validating session");
		FacebookSession::setDefaultApplication($aFBApp["I"], $aFBApp["S"]);		
				
		$oSession = new FacebookSession($psToken);
		try {
			$oSession->validate();
		} catch (Exception $ex) {
			cDebug::error("failed FB session: ". $ex->getMessage());
		}
		cDebug::write("FB session OK");
		
		//get the details of the user from facebook 
		cDebug::extra_debug("-- requesting from Graph");
		$oFBRequest = new FacebookRequest( $oSession, 'GET', '/me');
		$oFBResponse = $oFBRequest->execute();
		$oGraphObject = $oFBResponse->getGraphObject();
		//cDebug::vardump($oGraphObject);
		
		//remember this user
		cDebug::extra_debug("-- remembering user");
		$sUser = self::pr_setSessionUser($oGraphObject);
		self::pr_storeUserDetails( $psUserID, $oGraphObject);
		
		cDebug::leave();
		return $sUser;
	}
}
cFacebook_ServerSide::pr_init_objstore();

?>