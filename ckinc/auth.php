<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2014 -2015

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/
require_once "$phpinc/ckinc/facebook.php";
require_once "$phpinc/ckinc/header.php";
require_once("$phpinc/ckinc/objstoredb.php");

class cAuth{
	const ROLES_FOLDER = "[roles]";
	static $oObjStore = null;

	static function pr_init_objstore(){
		cDebug::enter();
		if (self::$oObjStore == null){
			$oStore = new cObjStoreDB();
			$oStore->realm = "AUTH";
			//$oStore->SHOW_SQL = true; //DEBUG
			$oStore->set_table("CKAUTH");	//possibly auth is a reserved name
			self::$oObjStore = $oStore;
		}
		cDebug::leave();
	}
	
	//**********************************************************
	public static function get_user(){
		cDebug::enter();

		$sUser = cFacebook_ServerSide::getSessionUser();
		cDebug::write("user is $sUser");
		cDebug::leave();
		return $sUser;
	}
	
	//**********************************************************
	public static function get_user_id(){
		cDebug::enter();
		$sUserID = cFacebook_ServerSide::getSessionUserID();
		cDebug::write("user ID is $sUserID");
		cDebug::leave();
		return $sUserID;
	}
		
	//**********************************************************
	public static function add_to_role($psUserID, $psRole){
		$oObjStore = self::$oObjStore;
		$aRoleDetails = $oObjStore->get_oldstyle(self::ROLES_FOLDER, $psRole);
		if (!$aRoleDetails) $aRoleDetails = [];
		if (! isset( $aRoleDetails[ $psUserID])){
			cDebug::write("Adding $psUserID to role $psRole");
			$aRoleDetails[$psUserID] = true;
			$oObjStore->put_oldstyle(self::ROLES_FOLDER, $psRole,$aRoleDetails );
		}
	}
	
	//**********************************************************
	public static function is_role( $psRole){
		global $root;
		cDebug::enter();
		$oObjStore = self::$oObjStore;
		
		//check whether this role is in the list of roles that the user has.
		$sUserID = self::get_user_id();
		if ($sUserID == null){
			cDebug::write("no user ID found in session");
			cDebug::leave();
			return false;
		}

		$aRoleDetails = $oObjStore->get_oldstyle(self::ROLES_FOLDER, $psRole);
		if (!$aRoleDetails){
			cDebug::write("role '$psRole' is not known");
			cDebug::leave();
			return false;
		}
		
		$bResult = isset($aRoleDetails[$sUserID]);
		cDebug::write("user '$sUserID' role '$psRole' result '$bResult'");
		cDebug::leave();
		return $bResult;
	}
	
	//**********************************************************
	public static function must_get_user(){
		cDebug::enter();
		$sUser = self::get_user();
		if (!$sUser) cDebug::error("user not logged in");

		cDebug::write("user is $sUser");
		cDebug::leave();
		return $sUser;
	}
	
}
cAuth::pr_init_objstore();

?>