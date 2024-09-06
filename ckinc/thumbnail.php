<?php
require_once  "$phpInc/ckinc/image.php";

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024
This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

class cBlobber {
    /** @var cSQLLite $oSqLDB  */
    static $oSQLDB = null;
    const BLOB_TABLE = "BLOBS";
    static $db_filename = "blobs.db";
    const COL_KEY = "k";
    const COL_MIME_TYPE = "m";
    const COL_BLOB = "b";
    const COL_DATE_ADDED = "da";

    //*************************************************************
    static function init_db($pDBFilename = null) {
        //cDebug::enter();
        if (!cCommon::is_string_empty($pDBFilename)) {
            $sExtension = substr($pDBFilename, -3);
            if ($sExtension !== ".db") cDebug::error("database filename must end with '.db'");
            self::$db_filename = $pDBFilename;
        }
        $oSqLDB = self::$oSQLDB;
        if ($oSqLDB == null) {
            cDebug::extra_debug("opening cSqlLite database: " . self::$db_filename);
            $oSqLDB = new cSqlLite(self::$db_filename);
            self::$oSQLDB = $oSqLDB;
        }
        self::pr_create_table();
        //cDebug::leave();
    }

    //*************************************************************
    //*************************************************************
    private static function pr_create_table() {
        //cDebug::enter();
        /** @var cSQLLite $oSqLDB  */
        $oSqLDB = self::$oSQLDB;
        $bTableExists = $oSqLDB->table_exists(self::BLOB_TABLE);
        if ($bTableExists) {
            //cDebug::extra_debug("table exists: " . self::BLOB_TABLE);
            return;
        }

        //--------------- create the table
        cDebug::extra_debug("table doesnt exist " . self::BLOB_TABLE);
        $sSQL =
            "CREATE TABLE `:table` ( " .
            ":key_col TEXT PRIMARY KEY, :mime_col TEXT not null, :blob_col BLOB, :date_col INTEGER" .
            ")";
        $sSQL = self::pr_replace_sql_params($sSQL);
        $oSqLDB->query($sSQL);
        cDebug::extra_debug("table created");
        // index and uniqueness are implicit for primary keys

        //cDebug::leave();
    }

    //*************************************************************
    private static function pr_replace_sql_params($psSQL) {
        $sSQL = str_replace(":table", self::BLOB_TABLE, $psSQL);
        $sSQL = str_replace(":key_col", self::COL_KEY, $sSQL);
        $sSQL = str_replace(":mime_col", self::COL_MIME_TYPE, $sSQL);
        $sSQL = str_replace(":blob_col", self::COL_BLOB, $sSQL);
        $sSQL = str_replace(":date_col", self::COL_DATE_ADDED, $sSQL);
        return $sSQL;
    }

    //*************************************************************
    //*************************************************************
    static function put($psID, $psFilename) {
    }

    //*************************************************************
    static function get($psID) {
    }

    //*************************************************************
    static function serve_image($psID) {
    }
}
cBlobber::init_db();
