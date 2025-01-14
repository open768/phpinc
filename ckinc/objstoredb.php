<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024
This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk
or leave a message on github

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
//
//% OBJSTOREDB - simplistic store objects without a relational database!
//%
//% solves Problem -  thousands files on busy websites that exceed inode quotas.
//%
 **************************************************************************/

require_once  cAppGlobals::$ckPhpInc . "/objstore.php";
require_once  cAppGlobals::$ckPhpInc . "/common.php";
require_once  cAppGlobals::$ckPhpInc . "/gz.php";
require_once  cAppGlobals::$ckPhpInc . "/hash.php";
require_once  cAppGlobals::$ckPhpInc . "/sqlite.php";

//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//%% Database 
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
class cOBjStoreDB {
    //statics and constants
    private static $warned_oldstyle = false;
    private static $oSQLite = null; //static as same database obj used between instances

    const DB_FILENAME = "objstore.db";
    const TABLE_NAME = "objstore";
    const COL_REALM = "RE";
    const COL_HASH = "HA";
    const COL_CONTENT = "CO";
    const COL_DATE = "DA";
    const COL_USER = "US";
    const OBJSTORE_REALM = "_objstore_";
    const OBJSTORE_CREATE_KEY = "created on";

    //class properties
    public $rootFolder = null;
    public $realm = null;
    public $expire_time = null;
    public $check_expiry = false;
    public $table = null;



    //#####################################################################
    //# constructor
    // by default all entries go into _objstore_
    //#####################################################################
    function __construct($psRealm, $psTable = null) {
        $this->realm = $psRealm;
        $this->table = $psTable;

        //check whether SQLLite has been 
        if (self::$oSQLite == null) {
            //cDebug::extra_debug("opening cSqlLite database");
            $oDB = new cSqlLite(self::DB_FILENAME);
            self::$oSQLite = $oDB;
        }

        if ($this->table == null) {
            cPageOutput::warning("table not provided for objstoredb realm $psRealm");
            $this->table = self::TABLE_NAME;
        }
        $this->pr_create_table();
    }


    //#####################################################################
    //# PRIVATES
    //#####################################################################

    private function replace_sql($psSQL) {
        $sSQL = str_replace(":table", $this->table, $psSQL);
        $sSQL = str_replace(":realm_col", self::COL_REALM, $sSQL);
        $sSQL = str_replace(":hash_col", self::COL_HASH, $sSQL);
        $sSQL = str_replace(":data_col", self::COL_CONTENT, $sSQL);
        $sSQL = str_replace(":date_col", self::COL_DATE, $sSQL);
        $sSQL = str_replace(":user_col", self::COL_USER, $sSQL);
        return $sSQL;
    }

    private function pr_create_table() {
        //cDebug::enter();
        $oSQL = self::$oSQLite;

        //check if table exists
        $bTableExists = $oSQL->table_exists($this->table);
        if ($bTableExists) {
            //cDebug::extra_debug("table '{$this->table}' exists");
            //cDebug::leave();
            return;
        }

        //table doesnt exist
        cDebug::extra_debug("table '{$this->table}' does not exist");
        $sSQL = "CREATE TABLE ':table' ( ':realm_col' TEXT not null, ':hash_col' TEXT not null, ':data_col' TEXT, ':user_col' TEXT,':date_col' DATETIME DEFAULT CURRENT_TIMESTAMP, primary key ( ':realm_col', ':hash_col'))";
        $sSQL = self::replace_sql($sSQL);
        if (cDebug::$SHOW_SQL) cDebug::extra_debug($sSQL);

        $oSQL->querySQL($sSQL);
        cDebug::extra_debug("table created");

        //create an index on the table
        $sSQL = "CREATE INDEX idx_users_:table on ':table' ( :realm_col, :user_col )";
        $sSQL = self::replace_sql($sSQL);
        if (cDebug::$SHOW_SQL) cDebug::extra_debug($sSQL);
        $oSQL->querySQL($sSQL);
        cDebug::extra_debug("index created");

        //add an Audit timestamp to say when this database was created
        cDebug::extra_debug("writing creation timestamp");
        $sNow = date('d-m-Y H:i:s');
        $oObj = new cOBjStoreDB(self::OBJSTORE_REALM);
        $oObj->put(self::OBJSTORE_CREATE_KEY, $sNow);
        //cDebug::leave();
    }

    //********************************************************************************
    private function pr_check_realm() {
        if ($this->realm == null) cDebug::error("realm is null");
    }

    //********************************************************************************
    /**
     * private function to warn tyhat oldstyle functions are deprecated.
     * These functions should be converted to ->get and ->put after migration from cobjstore
     * @todo
     * @return void
     */
    private function pr_warn_deprecated() {
        if (!self::$warned_oldstyle) {
            cDebug::write("oldstyle functions are to be deprecated");
            self::$warned_oldstyle = true;
        }
    }

    //#####################################################################
    //# PUBLICS - OLD STYLE - to be deprecated after everything is migrated
    //#####################################################################
    //********************************************************************************
    public function put_oldstyle($psFolder, $psFile, $poData) {
        //cDebug::enter();
        self::pr_warn_deprecated();

        $this->pr_check_realm();
        $sFullpath = $psFolder . "/" . $psFile;
        $this->put($sFullpath, $poData);
        //cDebug::leave();		
    }

    /**
     * this is a really dumb function - this will not scale when there are thousands of users
     * problem is every user will pull and push data needlessly into the database
     * what should happen is that 
     * **	there is a count stored for this object, 
     * 		a mutex used to increase that count, ( this needs to be protected)
     * 		the count used as part of the key to store the data
     * there should be an equivalent function to read an array.
     *
     * @todo rewrite function to remove dumbness
     * @param [type] $psFolder
     * @param [type] $psFile
     * @param [type] $poData
     * @return void
     */ //********************************************************************************
    public function add_to_array($psKey, $poData) {
        //cDebug::enter();
        self::pr_warn_deprecated();
        $aData = $this->get($psKey);
        if (!$aData) $aData = [];
        $aData[] = $poData; //add to the array
        $this->put($psKey, $aData, true);
        //cDebug::leave();
    }

    //********************************************************************************
    public function get_oldstyle($psFolder, $psFile) {
        //cDebug::enter();

        self::pr_warn_deprecated();
        $this->pr_check_realm();
        $oData = null;
        $sFullpath = $psFolder . "/" . $psFile;
        //cDebug::extra_debug("path is $sFullpath");
        if (cObjStore::file_exists($psFolder, $psFile)) {
            cDebug::extra_debug("migrating from cObjstore: $sFullpath, folder:$psFolder, file:$psFile");
            $oData = cObjStore::get_file($psFolder, $psFile);
            $this->put($sFullpath, $oData);
            cObjStore::kill_file($psFolder, $psFile);
        } else {
            //cDebug::extra_debug("no need to migrate");
            $oData = $this->get($sFullpath);
        }

        //cDebug::leave();
        return $oData;
    }

    //********************************************************************************
    /**
     * @param string $psFolder
     * @todo make this work on the objstore
     * @return void
     */
    public function kill_folder_oldstyle($psFolder) {
        //delete any physical files
        cObjStore::kill_folder($psFolder);

        //* @todo implement this */
        // find all folders that match and delete them 
    }

    //#####################################################################
    //# PUBLICS
    //#####################################################################

    //********************************************************************************
    public function set_table($psTable) {
        //cDebug::enter();

        cDebug::extra_debug("setting table to $psTable");
        if ($this->table !== $psTable) {
            $this->table = $psTable;
            $this->pr_create_table();
        }

        //cDebug::leave();		
    }

    //********************************************************************************
    public function put($psKey, $pvAnything, $pbOverride = true) {
        //cDebug::enter();
        $this->pr_check_realm();
        $oSQL = self::$oSQLite;

        //write the compressed string to the database
        $sHash = cHash::hash($psKey);
        //cDebug::extra_debug("hash: $sHash");

        $sSQL = "REPLACE INTO `:table` (:realm_col, :hash_col, :data_col, :date_col ) VALUES (:realm, :hash, :data, :date)";
        if (!$pbOverride) $sSQL = "INSERT INTO `:table` (:realm_col, :hash_col, :data_col, :date_col ) VALUES (:realm, :hash, :data, :date)";
        $sSQL = self::replace_sql($sSQL);
        if (cDebug::$SHOW_SQL) cDebug::extra_debug($sSQL);
        $oStmt = $oSQL->prepare($sSQL);
        $oStmt->bindValue(":realm", $this->realm);
        $oStmt->bindValue(":hash", $sHash);
        $oStmt->bindValue(":data", cGzip::encode($pvAnything));
        $oStmt->bindValue(":date", date('d-m-Y H:i:s'));
        $oSQL->exec_stmt($oStmt);

        //cDebug::leave();		
    }

    //********************************************************************************
    public function get($psKey, $pbCheckExpiry = false) {
        //cDebug::enter();
        //cDebug::extra_debug("Checking Realm");
        $this->pr_check_realm();

        //read from the database and decompress
        //cDebug::extra_debug("reading from table");
        $sHash = cHash::hash($psKey);
        $oSQL = self::$oSQLite;

        $sSQL = "SELECT :realm_col,:data_col,:date_col FROM `:table` where :realm_col=:realm AND :hash_col=:hash";
        $sSQL = self::replace_sql($sSQL);
        if (cDebug::$SHOW_SQL) cDebug::extra_debug($sSQL);

        //bind the values
        $oStmt = $oSQL->prepare($sSQL);
        $oStmt->bindValue(":realm", $this->realm);
        $oStmt->bindValue(":hash", $sHash);
        $oResultSet = $oSQL->exec_stmt($oStmt);
        if ($oResultSet == null) {
            cDebug::leave();
            return null;
        }

        $vResult = null;
        $aResult = $oResultSet->fetchArray(SQLITE3_ASSOC); //fine to use fetchArray as only looking for a single row
        if (is_array($aResult)) {
            $sEncoded = $aResult[self::COL_CONTENT];
            $vResult = cGzip::decode($sEncoded);

            if ($pbCheckExpiry) {
                $sItemDate = $aResult[SELF::COL_DATE]; //this is a string
                $dItemDate = strtotime($sItemDate);
                $iExpires = $dItemDate + $this->expire_time;
                $iDiff = $iExpires - time();
                if ($iDiff <= 0) {
                    cDebug::extra_debug("item has expired $iDiff seconds ago");
                    $this->kill($psKey);
                    $vResult = null;
                } else
                    cDebug::extra_debug_warning("cached: item will expire in $iDiff seconds");
            }
        }

        //cDebug::leave();		
        return $vResult;
    }


    //********************************************************************************
    public function kill($psKey) {
        //cDebug::enter();
        $this->pr_check_realm();
        $oSQL = self::$oSQLite;

        //read from the database and decompress
        $sHash = cHash::hash($psKey);

        $sSQL = "DELETE from `:table` where :realm_col=:realm AND :hash_col=:hash";
        $sSQL = self::replace_sql($sSQL);
        if (cDebug::$SHOW_SQL) cDebug::extra_debug("SQL: $sSQL");

        $oStmt = $oSQL->prepare($sSQL);
        $oStmt->bindValue(":realm", $this->realm);
        $oStmt->bindValue(":hash", $sHash);
        $oSQL->exec_stmt($oStmt);

        //cDebug::leave();
    }
}
