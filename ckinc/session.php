<?php
require_once  cAppGlobals::$ckPhpInc . "/debug.php";

class cSession {
    public static $THROW_ON_SESSION_ERROR = false;
    const SESSION_FOLDER = "[sessions]";

    //*******************************************************************
    public static function set_folder() {

        if (session_status() == PHP_SESSION_NONE)
            if (!headers_sent()) {    //this is vitally important - any header work must precede any html being sent
                //--- change the location where session data is stored
                $sFolder = cAppGlobals::$root . "/" . self::SESSION_FOLDER;
                if (!file_exists($sFolder))    mkdir($sFolder, 0700, true);
                session_save_path($sFolder);
            }
    }

    //*******************************************************************
    public static function clear_session() {
        cTracing::enter();
        @session_destroy();
        session_start();
        cTracing::leave();
    }

    //*******************************************************************
    public static function start() {
        cDebug::error("deprecated method called cSession::start");
    }

    //*******************************************************************
    public static function set($psName, $psValue) {
        global $_SESSION;
        $_SESSION[$psName] = $psValue;
    }

    //*******************************************************************
    public static function get($psName) {
        global $_SESSION;
        $sValue = null;
        if (isset($_SESSION[$psName]))    $sValue = $_SESSION[$psName];

        return $sValue;
    }
    //*******************************************************************
    public static function info() {
        if (!self::is_session_started())
            cDebug::error("no session");
        else {
            cDebug::write("session contents");
            cDebug::vardump($_SESSION);
        }
    }

    //*******************************************************************
    public static function is_session_started() {
        $bOut = session_status() === PHP_SESSION_ACTIVE;
        $bOut = $bOut  || headers_sent();
        return $bOut;
    }
}
