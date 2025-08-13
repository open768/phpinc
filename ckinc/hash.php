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
class cFileHasher {
    const HASH_FOLDER = "[cache]/[hash]";
    public static $CACHE_EXPIRY =  3600;  //(1 hour)
    const FOREVER = -1;

    //************************************************************************
    public static function get_folder($psHash, ?string $psRoot = null) {
        $d1 = substr($psHash, 0, 2);
        $d2 = substr($psHash, 2, 2);
        $sRootFolder = self::HASH_FOLDER;
        if ($psRoot !== null)
            $sRootFolder = $psRoot;

        return cAppGlobals::$root . "/$sRootFolder/$d1/$d2";
    }

    //************************************************************************
    public static function getPath($psHash, ?string $psRoot = null) {
        $sFolder = self::get_folder($psHash, $psRoot);
        $sFile = "$sFolder/$psHash";

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
    public static function get_hash(string $psHash, ?string $psRoot = null) {
        $oResponse = null;
        if (self::exists_hash($psHash, false, $psRoot)) {
            if (cDebug::$SHOW_CACHE_HIT) cDebug::write("exists in cache");
            $sFile = cFileHasher::getPath($psHash);
            //cDebug::extra_debug("$sFile");
            $oResponse = cGzip::readObj($sFile);
        } else
			if (cDebug::$SHOW_CACHE_HIT) cDebug::write("doesnt exist in cache");
        return $oResponse;
    }

    //************************************************************************
    public static function get(string $psAnything, ?string $psRoot = null) {
        $sHash = cHasher::hash($psAnything);
        $oThing = self::get_hash($sHash, $psRoot);
        return $oThing;
    }

    //************************************************************************
    public static function delete_hash($psHash, ?string $psRoot = null) {
        if (self::exists_hash($psHash, null, $psRoot)) {
            $sFile = cFileHasher::getPath($psHash, $psRoot);
            cDebug::write("deleting hash $psHash");
            unlink($sFile);
        }
    }

    public static function exists_hash(string $psHash, ?bool $pbCached = false, ?string $psRoot = null) {
        $sFile = cFileHasher::getPath($psHash, $psRoot);
        $bExists = file_exists($sFile);
        if (cDebug::$SHOW_HASHES) cDebug::write("hash: $bExists - $psHash");

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

    //************************************************************************
    public static function exists(string $psAnything, ?bool $pbCached = false, ?string $psRoot = null) {
        $sHash = cHasher::hash($psAnything);
        return self::exists_hash($sHash, $pbCached, $psRoot);
    }

    //************************************************************************
    public static function put_hash(string $psHash, $poObj, $pbOverwrite = false, ?string $psRoot = null) {
        if (!$pbOverwrite && cFileHasher::exists_hash($psHash, $psRoot))
            cDebug::error("hash exists: $psHash");
        else {
            $sFile = cFileHasher::getPath($psHash, $psRoot);
            cFileHasher::make_hash_folder($psHash, $psRoot);
            cGzip::writeObj($sFile, $poObj);
        }
    }
}
