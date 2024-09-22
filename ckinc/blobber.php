<?php
require_once  cAppGlobals::$phpInc . "/ckinc/image.php";
require_once  cAppGlobals::$phpInc . "/ckinc/hash.php";

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024
This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

class cBlobber {
    /** @var cSQLLite $oSqlDB  */
    private $oSqlDB = null;
    const BLOB_TABLE = "BLOBS";
    public $db_filename = "blobs.db";
    const COL_KEY = "k";
    const COL_MIME_TYPE = "m";
    const COL_BLOB = "b";
    const COL_DATE_ADDED = "da";

    //*************************************************************
    function __construct(string $pDBFilename = null) {
        //cDebug::enter();
        if (!cCommon::is_string_empty($pDBFilename)) {
            $sExtension = substr($pDBFilename, -3);
            if ($sExtension !== ".db") cDebug::error("database filename must end with '.db'");
            $this->db_filename = $pDBFilename;
        }
        $oSqlDB = $this->oSqlDB;
        if ($oSqlDB == null) {
            cDebug::extra_debug("opening cSqlLite database: " . $this->db_filename);
            $oSqlDB = new cSqlLite($this->db_filename);
            $this->oSqlDB = $oSqlDB;
        }
        self::pr_create_table();
        //cDebug::leave();
    }

    //*************************************************************
    //*************************************************************
    private function pr_create_table() {
        //cDebug::enter();
        /** @var cSQLLite $oSqlDB  */
        $oSqlDB = $this->oSqlDB;
        $bTableExists = $oSqlDB->table_exists(self::BLOB_TABLE);
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
        $oSqlDB->querySQL($sSQL);
        cDebug::extra_debug("table created");
        // index and uniqueness are implicit for primary keys

        //cDebug::leave();
    }

    //*************************************************************
    private static function pr_replace_sql_params(string $psSQL) {
        $sSQL = str_replace(":table", self::BLOB_TABLE, $psSQL);
        $sSQL = str_replace(":key_col", self::COL_KEY, $sSQL);
        $sSQL = str_replace(":mime_col", self::COL_MIME_TYPE, $sSQL);
        $sSQL = str_replace(":blob_col", self::COL_BLOB, $sSQL);
        $sSQL = str_replace(":date_col", self::COL_DATE_ADDED, $sSQL);
        return $sSQL;
    }

    //*************************************************************
    //*************************************************************
    function exists(string $psKey) {
        /** @var cSQLLite $oSqlDB  */
        $oSqlDB = $this->oSqlDB;
        $sKeyHash = cHash::hash($psKey);

        $sQL = "SELECT :key_col from `:table` where :key_col=:key";
        $sQL = self::pr_replace_sql_params($sQL);
        $oStmt = $oSqlDB->prepare($sQL);
        $oStmt->bindParam(":key", $sKeyHash);
        $oResultSet = $oSqlDB->exec_stmt($oStmt); //not worth changing to prep_exec_fetch
        $aData = $oResultSet->fetchArray(SQLITE3_ASSOC);
        return is_array($aData);
    }

    //*************************************************************
    function remove(string $psKey) {
        /** @var cSQLLite $oSqlDB  */
        $oSqlDB = $this->oSqlDB;
        $sKeyHash = cHash::hash($psKey);

        $sQL = "DELETE from `:table` where :key_col=:key";
        $sQL = self::pr_replace_sql_params($sQL);
        $oStmt = $oSqlDB->prepare($sQL);
        $oStmt->bindParam(":key", $sKeyHash);
        $oSqlDB->exec_stmt($oStmt); //not worth changing to prep_exec_fetch
    }

    //*************************************************************
    function put_obj(string $psKey, string $psMimeType, string $psBlobData) {
        /** @var cSQLLite $oSqlDB  */
        $oSqlDB = $this->oSqlDB;
        $sKeyHash = cHash::hash($psKey);

        $sQL = "INSERT into `:table` (:key_col, :mime_col, :blob_col, :date_col ) VALUES ( :key,  :mime, :data, :epoch)";
        $sQL = self::pr_replace_sql_params($sQL);
        $oStmt = $oSqlDB->prepare($sQL);
        $oStmt->bindParam(":key", $sKeyHash);
        $oStmt->bindParam(":mime", $psMimeType);
        $oStmt->bindParam(":data", $psBlobData, SQLITE3_BLOB);
        $iEpoch = time();
        $oStmt->bindParam(":epoch", $iEpoch);

        $oSqlDB->exec_stmt($oStmt);
    }

    //*************************************************************
    function get(string $psKey) {
        /** @var cSQLLite $oSqlDB  */
        $oSqlDB = $this->oSqlDB;
        $sKeyHash = cHash::hash($psKey);

        $sSQL = "SELECT :blob_col,:mime_col from `:table` where :key_col=:key";
        $sSQL = self::pr_replace_sql_params($sSQL);
        $oBinds = new cSqlBinds(); {
            $oBinds->add_bind(":key", $sKeyHash);
        }
        $aData = $oSqlDB->prep_exec_fetch($sSQL, $oBinds);
        if (count($aData) == 0)
            cDebug::error("unable to find $psKey");

        return $aData[0];
    }
}
