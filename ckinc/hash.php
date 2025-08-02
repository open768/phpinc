<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
//
// ** TBA allow instances of Hash to set their own folders. and set cache expiry policies**
// ** Currently this is set to cache folders **
//
//TODO: switch this to use sqllite in a single file - reduce the number of inodes used
//after switching to sqllite, move to use eloquent ORM
 **************************************************************************/

require_once  cAppGlobals::$ckPhpInc . "/debug.php";
require_once  cAppGlobals::$ckPhpInc . "/gz.php";
require_once  cAppGlobals::$ckPhpInc . "/sqlite.php";
require_once cAppGlobals::$ckPhpInc . "/eloquentorm.php";

//##########################################################################
class cHasher {
    public static function hash($psAnything) {
        //unique md5 - impossible that the reverse hash is the same as hash
        return  md5($psAnything) . md5(strrev($psAnything));
    }
}

//##########################################################################
class cHash {
    private static $oSqlDB = null;
    const CACHE_TABLE = "CACHE";
    public static $db_filename = "cache.db";

    const HASH_FOLDER = "[cache]/[hash]";
    const FOREVER = -1;
    private $HASH_REALM = "general";
    public static $CACHE_EXPIRY =  3600;  //(1 hour)
    public static $show_filenames = false;
    public static $show_hashes = false;
    public static $show_cache_hit = false;
    public static $shown_deprecated_warning = false;
    const COL_KEY =  "ky";
    const COL_MIME_TYPE = "mt";
    const COL_BLOB = "bl";
    const COL_DATE_ADDED = "da";
    const COL_TEXT = "txt";

    //####################################################################
    //####################################################################
    static function init() {
        if (self::$oSqlDB == null) {
            // cDebug::extra_debug("opening cSqlLite database: " . $this->db_filename);
            $oSqlDB = new cSqlLite(self::$db_filename);
            self::$oSqlDB = $oSqlDB;
        }
        self::pr_create_table();
    }

    //*************************************************************
    private static function pr_replace_sql_params(string $psSQL) {
        $sSQL = str_replace(":table", self::CACHE_TABLE, $psSQL);
        $sSQL = str_replace(":key_col", self::COL_KEY, $sSQL);
        $sSQL = str_replace(":mime_col", self::COL_MIME_TYPE, $sSQL);
        $sSQL = str_replace(":blob_col", self::COL_BLOB, $sSQL);
        $sSQL = str_replace(":date_col", self::COL_DATE_ADDED, $sSQL);
        $sSQL = str_replace(":text_col", self::COL_TEXT, $sSQL);
        return $sSQL;
    }

    //*************************************************************
    private static function pr_create_table() {
        //cTracing::enter();
        /** @var cSQLLite $oSqlDB  */
        $oSqlDB = self::$oSqlDB;
        $bTableExists = $oSqlDB->table_exists(self::CACHE_TABLE);
        if ($bTableExists) {
            cDebug::extra_debug("table exists: " . self::CACHE_TABLE);
            return;
        }

        //--------------- create the table
        cDebug::extra_debug("table doesnt exist " . self::CACHE_TABLE);
        $sSQL =
            "CREATE TABLE `:table` ( " .
            ":key_col TEXT PRIMARY KEY, :mime_col TEXT, :blob_col BLOB, :text_col TEXT, :date_col INTEGER" .
            ")";
        $sSQL = self::pr_replace_sql_params($sSQL);
        $oSqlDB->querySQL($sSQL);
        cDebug::extra_debug("table created");

        //cTracing::leave();
    }

    //####################################################################
    //####################################################################
    private static function pr__exists_hash($psHash, $pbCached = false) {
        $sFile = self::getPath($psHash);
        $bExists = file_exists($sFile);
        if (self::$show_hashes) cDebug::write("hash: $bExists - $psHash");

        // check the expiry date on the file - if its too old zap it
        if ($bExists && $pbCached && (self::$CACHE_EXPIRY <> self::FOREVER)) {
            cDebug::extra_debug("checking for hash expiry");
            $iDiff = time() - filemtime($sFile) - self::$CACHE_EXPIRY;
            if ($iDiff > 0) {
                cDebug::write("hash file expired $psHash - $iDiff seconds ago");
                unlink($sFile);
                $bExists = false;
            } else
                cDebug::write("hash file will expire in $iDiff seconds");
        }

        return $bExists;
    }

    //####################################################################
    //####################################################################

    //************************************************************************
    public static function delete_hash($psHash) {
        if (self::pr__exists_hash($psHash)) {
            $sFile = self::getPath($psHash);
            cDebug::write("deleting hash $psHash");
            unlink($sFile);
        }
    }

    //************************************************************************
    public static function get_folder($psHash) {


        $d1 = substr($psHash, 0, 2);
        $d2 = substr($psHash, 2, 2);
        return cAppGlobals::$root . "/" . self::HASH_FOLDER . "/$d1/$d2";
    }


    //************************************************************************
    public static function getPath($psHash) {
        if (!self::$shown_deprecated_warning) {
            cPageOutput::warning("file based hash will be deprecated");
            self::$shown_deprecated_warning = true;
        }
        $sFolder = self::get_folder($psHash);
        $sFile = "$sFolder/$psHash";
        if (self::$show_filenames) cDebug::write("hash_file: $sFile");

        return $sFile;
    }

    //************************************************************************
    public static function make_hash_folder($psHash) {
        $sFolder = self::get_folder($psHash);
        if (!is_dir($sFolder)) {
            cDebug::write("making folder: for hash $psHash");
            mkdir($sFolder, 0700, true);
        }
        return $sFolder;
    }

    //************************************************************************
    public static function pr__put_obj($psHash, $poObj, $pbOverwrite = false, $pbCached = false) {
        $sFile = self::getPath($psHash);
        if (!$pbOverwrite && self::pr__exists_hash($psHash))
            cDebug::error("hash exists: $psHash");
        else {
            self::make_hash_folder($psHash);
            cGzip::writeObj($sFile, $poObj);
        }
    }

    //************************************************************************
    public static function pr__get_obj($psHash, $pbCached = false) {
        $oResponse = null;
        if (self::pr__exists_hash($psHash)) {
            if (self::$show_cache_hit) cDebug::write("exists in cache");
            $sFile = self::getPath($psHash);
            //cDebug::extra_debug("$sFile");
            $oResponse = cGzip::readObj($sFile);
        } else
			if (self::$show_cache_hit) cDebug::write("doesnt exist in cache");
        return $oResponse;
    }

    //************************************************************************
    public static function get_old_style($psAnything, $pbCached = false) {
        $sHash = cHasher::hash($psAnything);
        $oThing = self::pr__get_obj($sHash, $pbCached);
        return $oThing;
    }

    //************************************************************************
    public static function put($psAnything, $poObj, $pbOverwrite = false, $pbCached = false) {
        $sHash = cHasher::hash($psAnything);
        self::pr__put_obj($sHash, $poObj, $pbOverwrite);
    }

    //************************************************************************
    public static function exists_old_style($psAnything, $pbCached = false) {
        $sHash = cHasher::hash($psAnything);
        return self::pr__exists_hash($sHash, $pbCached);
    }

    //************************************************************************
    public static function delete($psAnything) {
        cTracing::enter();
        $sHash = cHasher::hash($psAnything);
        self::delete_hash($sHash);
        cTracing::leave();
    }
}
cHash::init();
