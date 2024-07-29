<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 -2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/
require_once "$phpInc/ckinc/facebook.php";
require_once "$phpInc/ckinc/header.php";
require_once  "$phpInc/ckinc/objstoredb.php";

class cAuth
{
    const ROLES_FOLDER = "[roles]";
    const OBJDB_REALM = "AUTH";
    const OBJDB_TABLE = "CKAUTH";

    static $objstoreDB = null;

    static function init_obj_store_db()
    {
        cDebug::enter();
        if (self::$objstoreDB == null) {
            self::$objstoreDB = new cObjStoreDB(self::OBJDB_REALM, self::OBJDB_TABLE);
        }
        cDebug::leave();
    }

    //**********************************************************
    public static function get_user()
    {
        cDebug::enter();

        $sUser = cFacebook_ServerSide::getSessionUser();
        cDebug::write("user is $sUser");
        cDebug::leave();
        return $sUser;
    }

    //**********************************************************
    public static function get_user_id()
    {
        cDebug::enter();
        $sUserID = cFacebook_ServerSide::getSessionUserID();
        cDebug::write("user ID is $sUserID");
        cDebug::leave();
        return $sUserID;
    }

    //**********************************************************
    public static function add_to_role($psUserID, $psRole)
    {
        /** @var cObjStoreDB **/
        $oDB = self::$objstoreDB;
        $aRoleDetails = $oDB->get_oldstyle(self::ROLES_FOLDER, $psRole);
        if (!$aRoleDetails) $aRoleDetails = [];
        if (!isset($aRoleDetails[$psUserID])) {
            cDebug::write("Adding $psUserID to role $psRole");
            $aRoleDetails[$psUserID] = true;
            $oDB->put_oldstyle(self::ROLES_FOLDER, $psRole, $aRoleDetails);
        }
    }

    //**********************************************************
    public static function is_role($psRole)
    {
        global $root;
        cDebug::enter();
        /** @var cObjStoreDB **/
        $oDB = self::$objstoreDB;

        //check whether this role is in the list of roles that the user has.
        $sUserID = self::get_user_id();
        if ($sUserID == null) {
            cDebug::write("no user ID found in session");
            cDebug::leave();
            return false;
        }

        $aRoleDetails = $oDB->get_oldstyle(self::ROLES_FOLDER, $psRole);
        if (!$aRoleDetails) {
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
    public static function must_get_user()
    {
        cDebug::enter();
        $sUser = self::get_user();
        if (!$sUser) cDebug::error("user not logged in");

        cDebug::write("user is $sUser");
        cDebug::leave();
        return $sUser;
    }
}
cAuth::init_obj_store_db();
