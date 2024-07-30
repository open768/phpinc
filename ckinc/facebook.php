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
use Facebook\GraphUser;

//###########################################################################
//#
//###########################################################################
class cFacebookID
{
    public $id = null;
    public $secret = null;

    function __construct($psID, $psSecret)
    {
        if (!cCommon::is_string_set($psID)) cDebug::error("no ID set");
        $this->id = $psID;
        $this->secret = $psSecret;
    }
}

//###########################################################################
//#
//###########################################################################
class cFacebook_ServerSide
{
    const FB_USER_FOLDER = "[facebook]";
    const FB_ALL_USERS = "[all users]";
    const FB_SESS_USER = "fbuser";
    const FB_SESS_USERID = "fbuserid";
    const OBJSTORE_REALM = "FB";
    const OBJSTORE_TABLE = "FB";
    static $objstoreDB = null;


    static function init_obj_store_db()
    {
        cDebug::enter();
        if (self::$objstoreDB == null)
            self::$objstoreDB = new cObjStoreDB(self::OBJSTORE_REALM, self::OBJSTORE_TABLE);
        cDebug::leave();
    }
    //*******************************************************************
    public static function is_facebook()
    {
        //force on query string
        if (isset($_GET["fb"])) return true;

        //user agent is like facebookexternalhit/1.1
        return preg_match("/facebook/", strtolower($_SERVER['HTTP_USER_AGENT']));
    }


    //*******************************************************************
    private static function pr_setSessionUser($poGraphObject)
    {
        cDebug::enter();
        $sUser = $poGraphObject->getProperty("name");
        $sID = $poGraphObject->getProperty("id");
        $_SESSION[self::FB_SESS_USER] = $sUser;
        $_SESSION[self::FB_SESS_USERID] = $sID;
        cDebug::leave();
        return $sUser;
    }

    //*******************************************************************
    public static function getSessionUser()
    {
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
    public static function getSessionUserID()
    {
        //get the user from the session
        if (isset($_SESSION[self::FB_SESS_USERID]))
            return $_SESSION[self::FB_SESS_USERID];
        else {
            cDebug::write("no facebook session userid");
            return null;
        }
    }

    //*******************************************************************
    public static function getStoredUser($psUserID)
    {
        cDebug::enter();

        if (cDebug::$IGNORE_CACHE)
            cDebug::extra_debug("nocache - not looking here");
        else {
            $sUser = self::getSessionUser();

            //is this user details stored?
            if (!$sUser) {
                cDebug::write("username not in session, checking if known");

                /** @var cObjStoreDB **/
                $oDB = self::$objstoreDB;
                $oGraphObject = $oDB->get_oldstyle(self::FB_USER_FOLDER, $psUserID);
                if ($oGraphObject) {
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
    private static function pr_storeUserDetails($psUserID, $poData)
    {
        cDebug::enter();

        /** @var cObjStoreDB **/
        $oDB = self::$objstoreDB;

        //store the details
        $oDB->put_oldstyle(self::FB_USER_FOLDER, $psUserID, $poData);

        //add to list of FB users 
        //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        //%this is not scalable imagine if there were thousands of users - it would kill the PHP server 
        //%TBD to find a better way of working with arrays.
        //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        $aFBUsers = $oDB->get_oldstyle(self::FB_USER_FOLDER, self::FB_ALL_USERS);
        if (!$aFBUsers) $aFBUsers = [];
        $aFBUsers[$psUserID] = 1;
        $oDB->put_oldstyle(self::FB_USER_FOLDER, self::FB_ALL_USERS, $aFBUsers);

        cDebug::leave();
    }

    //*******************************************************************
    public static function getAppID()
    {
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
    public static function getUserIDDetails($psUserID, $psToken)
    {
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
        $oGraphObject = $oFBResponse->getGraphObject();
        //cDebug::vardump($oGraphObject);

        //remember this user
        cDebug::extra_debug("-- remembering user");
        $sUser = self::pr_setSessionUser($oGraphObject);
        self::pr_storeUserDetails($psUserID, $oGraphObject);

        cDebug::leave();
        return $sUser;
    }
}
cFacebook_ServerSide::init_obj_store_db();
