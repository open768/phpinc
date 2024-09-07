<?php
require_once  "$phpInc/ckinc/debug.php";
require_once  "$phpInc/ckinc/common.php";
require_once  "$phpInc/ckinc/objstoredb.php";

// @todo the following is deprecated FB now only supports their graph API
// https://github.com/facebookarchive/php-graph-sdk
//load facebook classes
require_once  "$phpInc/extra/facebook/autoload.php";

use Facebook\FacebookSession;
use Facebook\FacebookRequest;

//###########################################################################
//#
//###########################################################################
class cFacebookID {
    public $id = null;
    public $secret = null;

    function __construct($psID, $psSecret) {
        if (!cCommon::is_string_set($psID)) cDebug::error("no ID set");
        $this->id = $psID;
        $this->secret = $psSecret;
    }
}

//###########################################################################
//#
//###########################################################################
class cFacebook_ServerSide {
    const FB_USER_FOLDER = "[facebook]";
    const FB_ALL_USERS = "[all users]";
    const FB_SESS_USER = "fbuser";
    const FB_SESS_USERID = "fbuserid";
    const OBJSTORE_REALM = "FB";
    static $objstoreDB = null;


    static function init_obj_store_db() {
        if (self::$objstoreDB == null)
            self::$objstoreDB = new cObjStoreDB(self::OBJSTORE_REALM, "FB");
    }
    //*******************************************************************
    public static function is_facebook() {
        //force on query string
        $sFB = cHeader::get("fb");
        if (!cCommon::is_string_empty($sFB)) return true;

        //user agent is like facebookexternalhit/1.1
        return preg_match("/facebook/", strtolower($_SERVER['HTTP_USER_AGENT']));
    }


    //*******************************************************************
    private static function pr_setSessionUser($poGraphObject) {
        cDebug::enter();
        $sClass = get_class($poGraphObject);
        if ($sClass !== "Facebook\GraphObject") cDebug::error("class Facebook\GraphObject expected, found $sClass");
        $sUser = $poGraphObject->getProperty("name");
        $sID = $poGraphObject->getProperty("id");
        $_SESSION[self::FB_SESS_USER] = $sUser;
        $_SESSION[self::FB_SESS_USERID] = $sID;
        cDebug::leave();
        return $sUser;
    }

    //*******************************************************************
    public static function getSessionUser() {
        cDebug::enter();
        $sUser = null;
        //get the user from the session
        if (isset($_SESSION[self::FB_SESS_USER]))
            if (!cDebug::$IGNORE_CACHE) {
                cDebug::extra_debug("using session user");
                $sUser = $_SESSION[self::FB_SESS_USER];
            } else
                cDebug::extra_debug("ignoring session user");
        else
            cDebug::write("no facebook session user");

        cDebug::leave();
        return $sUser;
    }

    //*******************************************************************
    public static function getSessionUserID() {
        //get the user from the session
        if (isset($_SESSION[self::FB_SESS_USERID]))
            return $_SESSION[self::FB_SESS_USERID];
        else {
            cDebug::write("no facebook session userid");
            return null;
        }
    }

    //*******************************************************************
    public static function getStoredUser($psUserID) {
        cDebug::enter();
        $sUser = null;

        if (cDebug::$IGNORE_CACHE)
            cDebug::extra_debug("nocache - not looking here");
        else {
            $sUser = self::getSessionUser();

            //is this user details stored?
            if (!$sUser) {
                cDebug::write("username not in session, checking if known");

                $oUser = self::get_userDetails($psUserID);
                if ($oUser) {
                    cDebug::write("found stored user");
                    $sUser = self::pr_setSessionUser($oUser);
                }
            }
        }

        cDebug::leave();
        return $sUser;
    }

    //*******************************************************************
    private static function pr_storeUserDetails($psUserID, $poData) {
        cDebug::enter();

        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;

        //store the details
        $oDB->put(self::FB_USER_FOLDER . "/$psUserID", $poData);
        self::add_to_index($psUserID);

        cDebug::leave();
    }

    //*******************************************************************
    static function add_to_index($psUserID) {
        cDebug::enter();
        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;
        $oDB->add_to_array(self::FB_USER_FOLDER . "/" . self::FB_ALL_USERS, $psUserID);
        cDebug::leave();
    }

    //*******************************************************************
    static function get_userDetails($psUserID) {
        cDebug::enter();
        if (cCommon::is_string_empty($psUserID)) cDebug::error("no user ID");

        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;
        $sFolder = self::FB_USER_FOLDER;
        cDebug::extra_debug("folder:$sFolder file:$psUserID");

        $oUser = $oDB->get(self::FB_USER_FOLDER . "/$psUserID");

        cDebug::leave();
        return $oUser;
    }

    //*******************************************************************
    static function get_index() {
        cDebug::enter();
        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;
        $aData = $oDB->get(self::FB_USER_FOLDER . "/" . self::FB_ALL_USERS);
        cDebug::leave();
        return $aData;
    }

    //*******************************************************************
    public static function getAppID() {
        $sID = null;
        cDebug::enter();
        if (cDebug::is_localhost()) {
            cDebug::write("using development credentials for localhost");
            $oID = new cFacebookID(cAppSecret::FB_DEV_APP, cAppSecret::FB_DEV_SECRET);
        } else
            $oID = new cFacebookID(cAppSecret::FB_APP, cAppSecret::FB_SECRET);

        cDebug::leave();
        return $oID;
    }

    //*******************************************************************
    public static function getUserName($psUserID, $psToken) {
        cDebug::enter();
        $oAppID = self::getAppID();

        cDebug::extra_debug("-- validating session");
        FacebookSession::setDefaultApplication($oAppID->id, $oAppID->secret);

        $oSession = new FacebookSession($psToken);
        try {
            $oSession->validate();
        } catch (Exception $ex) {
            cDebug::error("failed FB session: " . $ex->getMessage());
        }
        cDebug::write("FB session OK");

        //get the details of the user from facebook 
        cDebug::extra_debug("-- requesting from Graph");
        $oFBRequest = new FacebookRequest($oSession, 'GET', '/me');
        $oFBResponse = $oFBRequest->execute();
        $oGraph = $oFBResponse->getGraphObject();
        cDebug::vardump($oGraph);

        //remember this user
        cDebug::extra_debug("-- remembering user");
        $sUser = self::pr_setSessionUser($oGraph);
        cDebug::write("User is '$sUser'");
        self::pr_storeUserDetails($psUserID, $oGraph);

        cDebug::leave();
        return $sUser;
    }
}
cFacebook_ServerSide::init_obj_store_db();
