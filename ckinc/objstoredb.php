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

require_once  "$phpInc/ckinc/objstore.php";
require_once  "$phpInc/ckinc/common.php";
require_once  "$phpInc/ckinc/gz.php";
require_once  "$phpInc/ckinc/hash.php";
require_once  "$phpInc/ckinc/sqlite.php";

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
    public $SHOW_SQL = false;
    public $rootFolder = null;
    public $realm = null;
    public $expire_time = null;
    public $check_expiry = false;
    public $table = null;

    /*
        magic strings in this code
        :t - table
        :r - column
        :h - hash
        :c - content
        :d - timestamp
        :u - userid
    */


    //#####################################################################
    //# constructor
    // by default all entries go into _objstore_
    //#####################################################################
    function __construct($psRealm, $psTable = null) {
        $this->realm = $psRealm;
        $this->table = $psTable;

        //check whether SQLLite has been 
        if (self::$oSQLite == null) {
            cDebug::extra_debug("opening cSqlLite database");
            $oDB = new cSqlLite(self::DB_FILENAME);
            self::$oSQLite = $oDB;
        } else
            cDebug::extra_debug(" cSqlLite instance exists");

        if ($this->table == null) {
            cPageOutput::warning("table not provided for objstoredb realm $psRealm");
            $this->table = self::TABLE_NAME;
        }
        $this->pr_create_table();
    }


    //#####################################################################
    //# PRIVATES
    //#####################################################################

    private function pr_create_table() {
        //cDebug::enter();
        $oSQL = self::$oSQLite;


        //check if table exists
        $bTableExists = $oSQL->table_exists($this->table);
        if ($bTableExists) {
            cDebug::extra_debug("table '{$this->table}' exists");
            //cDebug::leave();
            return;
        }

        //table doesnt exist
        cDebug::extra_debug("table '{$this->table}' does not exist");
        $sSQL = "CREATE TABLE ':t' ( ':r' TEXT not null, ':h' TEXT not null, ':c' TEXT, ':u' TEXT,':d' DATETIME DEFAULT CURRENT_TIMESTAMP, primary key ( ':r', ':h'))";
        $sSQL = str_replace(":t", $this->table, $sSQL);
        $sSQL = str_replace(":r", self::COL_REALM, $sSQL);
        $sSQL = str_replace(":h", self::COL_HASH, $sSQL);
        $sSQL = str_replace(":c", self::COL_CONTENT, $sSQL);
        $sSQL = str_replace(":d", self::COL_DATE, $sSQL);
        $sSQL = str_replace(":u", self::COL_USER, $sSQL);
        if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);

        $oStmt = $oSQL->query($sSQL);
        cDebug::extra_debug("table created");

        //create an index on the table
        $sSQL = "CREATE INDEX idx_users on ':t' ( :r, :u )";
        $sSQL = str_replace(":t", $this->table, $sSQL);
        $sSQL = str_replace(":r", self::COL_REALM, $sSQL);
        $sSQL = str_replace(":u", self::COL_USER, $sSQL);
        if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);
        $oStmt = $oSQL->query($sSQL);
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
    public function add_to_array_oldstyle($psFolder, $psFile, $poData) {
        //cDebug::enter();

        self::pr_warn_deprecated();
        $sFullpath = $psFolder . "/" . $psFile;
        $aData = $this->get($sFullpath);
        if (!$aData) $aData = [];
        $aData[] = $poData; //add to the array
        $this->put($sFullpath, $aData, true);
        cDebug::leave();
        //return $aData;
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
    public function kill_oldstyle($psFolder, $psFile) {
        cDebug::enter();
        self::pr_warn_deprecated();
        $sFullpath = $psFolder . "/" . $psFile;
        $this->kill($psFolder, $psFile);
        $this->kill($sFullpath);
        cDebug::leave();
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

        $sSQL = "REPLACE INTO ':t' (:r, :h, :c, :d ) VALUES (?, ?, ?, ?)";
        if (!$pbOverride) $sSQL = "INSERT INTO ':t' (:r, :h, :c, :d ) VALUES (?, ?, ?, ?)";
        $sSQL = str_replace(":t", $this->table, $sSQL);
        $sSQL = str_replace(":r", self::COL_REALM, $sSQL);
        $sSQL = str_replace(":h", self::COL_HASH, $sSQL);
        $sSQL = str_replace(":c", self::COL_CONTENT, $sSQL);
        $sSQL = str_replace(":d", self::COL_DATE, $sSQL);
        if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);
        $oStmt = $oSQL->prepare($sSQL);
        $oStmt->bindValue(1, $this->realm);
        $oStmt->bindValue(2, $sHash);
        $oStmt->bindValue(3, cGzip::encode($pvAnything));
        $oStmt->bindValue(4, date('d-m-Y H:i:s'));
        $oResultSet = $oSQL->exec_stmt($oStmt);

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

        $sSQL = "SELECT :r,:c,:d FROM ':t' where :r=? AND :h=?";
        $sSQL = str_replace(":t", $this->table, $sSQL);
        $sSQL = str_replace(":r", self::COL_REALM, $sSQL);
        $sSQL = str_replace(":h", self::COL_HASH, $sSQL);
        $sSQL = str_replace(":c", self::COL_CONTENT, $sSQL);
        $sSQL = str_replace(":d", self::COL_DATE, $sSQL);
        if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);

        //bind the values
        $oStmt = $oSQL->prepare($sSQL);
        $oStmt->bindValue(1, $this->realm);
        $oStmt->bindValue(2, $sHash);
        $oResultSet = $oSQL->exec_stmt($oStmt);
        if ($oResultSet == null) {
            cDebug::leave();
            return null;
        }

        $vResult = null;
        $aResult = $oResultSet->fetchArray();
        if (is_array($aResult)) {
            $sEncoded = $aResult[1];
            $vResult = cGzip::decode($sEncoded);

            if ($pbCheckExpiry) {
                $sItemDate = $aResult[2]; //this is a string
                $dItemDate = strtotime($sItemDate);
                $iExpires = $dItemDate + $this->expire_time;
                $iDiff = $iExpires - time();
                if ($iDiff <= 0) {
                    cDebug::extra_debug("item has expired $iDiff seconds ago");
                    $this->kill($psKey);
                    $vResult = null;
                } else
                    cDebug::extra_debug("<font style='background-color:lavender'>cached: item will expire in $iDiff seconds</font>");
            }
        }

        //cDebug::leave();		
        return $vResult;
    }


    //********************************************************************************
    public function kill($psKey) {
        cDebug::enter();
        $this->pr_check_realm();
        $oSQL = self::$oSQLite;

        //read from the database and decompress
        $sHash = cHash::hash($psKey);
        cDebug::extra_debug("hash: $sHash");

        $sSQL = "DELETE from ':t' where :r=? AND :h=?";
        $sSQL = str_replace(":t", $this->table, $sSQL);
        $sSQL = str_replace(":r", self::COL_REALM, $sSQL);
        $sSQL = str_replace(":h", self::COL_HASH, $sSQL);
        if ($this->SHOW_SQL) cDebug::extra_debug("SQL: $sSQL");

        $oStmt = $oSQL->prepare($sSQL);
        $oStmt->bindValue(1, $this->realm);
        $oStmt->bindValue(2, $sHash);
        $oResultSet = $oSQL->exec_stmt($oStmt);

        cDebug::leave();
    }
}
