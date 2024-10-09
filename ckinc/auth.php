<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 -2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/
require_once cAppGlobals::$ckPhpInc . "/facebook.php";
require_once cAppGlobals::$ckPhpInc . "/header.php";
require_once  cAppGlobals::$ckPhpInc . "/objstoredb.php";

class cAuth {
    const ROLES_FOLDER = "[roles]";
    const OBJDB_REALM = "AUTH";
    const ADMIN_ROLE = "ckadmin"; //fixed for everything
    const YES = "yes";
    const ID_FILENAME = "ckadmin.json";

    static $objstoreDB = null;

    static function init_obj_store_db() {
        if (self::$objstoreDB == null) {
            self::$objstoreDB = new cObjStoreDB(self::OBJDB_REALM, "CKAUTH");
        }
    }

    //**********************************************************
    public static function get_user() {
        cDebug::enter();

        $sUser = cFacebook_ServerSide::getSessionUser();
        cDebug::write("user is $sUser");
        cDebug::leave();
        return $sUser;
    }

    //**********************************************************
    public static function get_user_id() {
        cDebug::enter();
        $sUserID = cFacebook_ServerSide::getSessionUserID();
        cDebug::write("user ID is $sUserID");
        cDebug::leave();
        return $sUserID;
    }

    //**********************************************************
    public static function add_to_role($psUserID, $psRole) {
        cDebug::enter();

        $oDB = self::$objstoreDB;
        $aRoleDetails = $oDB->get(self::ROLES_FOLDER . "/" . $psRole);
        if (!$aRoleDetails) $aRoleDetails = [];
        if (!isset($aRoleDetails[$psUserID])) {
            cDebug::write("Adding $psUserID to role $psRole");
            $aRoleDetails[$psUserID] = true;
            $oDB->put(self::ROLES_FOLDER . "/$psRole", $aRoleDetails);
        } else
            cDebug::write("user $psUserID allready has role $psRole ");
        cDebug::leave();
    }

    //**********************************************************
    public static function is_role($psRole) {

        cDebug::enter();
        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;

        //check whether this role is in the list of roles that the user has.
        $sUserID = self::get_user_id();
        if ($sUserID == null) {
            cDebug::write("no user ID found in session");
            cDebug::leave();
            return false;
        }

        $aRoleDetails = $oDB->get(self::ROLES_FOLDER . "/$psRole");
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
    public static function must_get_user() {
        cDebug::enter();
        $sUser = self::get_user();
        if (!$sUser) cDebug::error("user not logged in");

        cDebug::leave();
        return $sUser;
    }

    //**********************************************************
    public static function current_user_is_admin() {
        cDebug::enter();
        $sUser = self::get_user();

        if (!$sUser)
            return "not logged in";

        $sIsAdmin = "no";
        $bisAdmin = cAuth::is_role(self::ADMIN_ROLE);
        if ($bisAdmin)
            $sIsAdmin = self::YES;

        cDebug::extra_debug("result is $sIsAdmin");
        cDebug::leave();
        return $sIsAdmin;
    }

    //**********************************************************
    public static function check_for_admin_id_file() {
        cDebug::enter();

        //--------check that file is there -------------------------
        $filename = cAppGlobals::$appConfig . "/" . self::ID_FILENAME;
        cDebug::write("checking for ID file '$filename'");
        if (!file_exists($filename)) {
            cDebug::write("file doesnt exist - no users to ad to admin role");
            return;
        }
        cDebug::extra_debug("ID file found");

        //--------read in raw json -------------------------
        $sRaw = file_get_contents($filename);
        $oJson = json_decode($sRaw);
        if ($oJson == null) {
            cDebug::vardump($sRaw);
            cDebug::error("unable to read Json content in $filename");
        }
        cDebug::write("file contains json");

        //--------check that the structure is valid --------------------
        if (!$oJson->admin_ids) {
            cDebug::vardump($sRaw);
            cDebug::error("no property admin_ids found in json");
        }
        $aIds = $oJson->admin_ids;
        if (!is_array($aIds)) cDebug::error("admin_ids doesnt contain an array");
        cDebug::write("file contains json");

        //---------process the IDs -----------------------------
        cDebug::vardump($aIds, true);
        foreach ($aIds as $sID) {
            cDebug::extra_debug("adding user $sID");
            self::add_to_role($sID, self::ADMIN_ROLE);
        }
        cDebug::extra_debug("deleting json file");
        unlink($filename);


        cDebug::leave();
    }
}
cAuth::init_obj_store_db();
