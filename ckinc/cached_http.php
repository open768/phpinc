<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 -2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

require_once  cAppGlobals::$ckPhpInc . "/http.php";
require_once  cAppGlobals::$ckPhpInc . "/debug.php";
require_once  cAppGlobals::$ckPhpInc . "/hash.php";
require_once  cAppGlobals::$ckPhpInc . "/objstoredb.php";

class cCachedHttp {
    const INFINITE = -1;
    private static $objstoreDB = null;
    public $CACHE_EXPIRY = 3600;  //(seconds)
    public $USE_CURL = true;
    public $ALLOW_SELF_SIGNED_CERT = true;
    private $oCache = null;
    private $sCacheFile = null;
    public     $fileHashing = true;
    public     $show_progress = false;
    public  $HTTPS_CERT_FILENAME = null;
    const OBJDB_REALM = "CACHTTP";
    const DEFAULT_CACHE_EXPIRY = 3600;

    //********************************************************************
    public static function init_obj_store_db() {
        if (!self::$objstoreDB) {
            $oDB = new cObjStoreDB(self::OBJDB_REALM, "HTMLCAC");
            $oDB->expire_time = SELF::DEFAULT_CACHE_EXPIRY;

            self::$objstoreDB = $oDB;
        }
    }
    //*****************************************************************************
    public function deleteCachedURL($psURL) {
        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;
        $oDB->kill($psURL);
    }

    //*****************************************************************************
    public function getCachedUrl($psURL) {
        cTracing::enter();
        $oResult = $this->pr_do_get($psURL, false);
        cTracing::leave();
        return $oResult;
    }

    //*****************************************************************************
    public function getXML($psURL) {
        cTracing::enter();

        cDebug::write("Getting XML from: $psURL");
        $sXML = $this->getCachedUrl($psURL);
        cDebug::write("converting string to XML: ");
        $oXML = simplexml_load_string($sXML);
        cDebug::write("finished conversion");

        cTracing::leave();

        return $oXML;
    }

    //*****************************************************************************
    public function is_cached($psURL, $pbCheckExpiry) {
        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;
        $oData = $oDB->get($psURL, $pbCheckExpiry);
        $bIsCached = $oData !== null;

        return $bIsCached;
    }

    //*****************************************************************************
    public function getCachedUrltoFile($psURL) {    //for large files too big for sql
        cTracing::enter();
        $sHash = cHash::hash($psURL);
        cHash::$CACHE_EXPIRY = $this->CACHE_EXPIRY;        //dangerous fudge TODO
        $sPath = cHash::getPath($sHash);

        if (!cHash::exists($sHash)) {
            cHash::make_hash_folder($sHash);
            $oHttp = new cHttp();
            $oHttp->ALLOW_SELF_SIGNED_CERT = $this->ALLOW_SELF_SIGNED_CERT;
            $oHttp->show_progress = true;
            $oHttp->fetch_to_file($psURL, $sPath, true, 60, true);
        }
        cTracing::leave();
        return $sPath;
    }

    //*****************************************************************************
    public function getCachedJson($psURL, $pbCheckExpiry = true) {
        //cTracing::enter();
        $oResult = $this->pr_do_get($psURL, true, $pbCheckExpiry);
        //cTracing::leave();
        return $oResult;
    }

    //*****************************************************************************
    //*
    //*****************************************************************************
    private function pr_do_get($psURL, $pbJson, $pbCheckExpiry = true) {
        //cTracing::enter();

        $oHttp = new cHttp();
        $oHttp->USE_CURL = $this->USE_CURL;
        $oHttp->ALLOW_SELF_SIGNED_CERT = $this->ALLOW_SELF_SIGNED_CERT;
        $oHttp->show_progress = $this->show_progress;
        $oHttp->HTTPS_CERT_FILENAME = $this->HTTPS_CERT_FILENAME;

        $oResponse = null;
        //cDebug::write("requested url:$psURL");

        /** @var cObjStoreDB $oDB **/
        $oDB = self::$objstoreDB;
        $oData = null;
        if (!cDebug::$IGNORE_CACHE)  $oData = $oDB->get($psURL, $pbCheckExpiry);
        if ($oData == null) {
            cDebug::write("fetching live url:$psURL");
            if ($pbJson)
                $oData = $oHttp->getJson($psURL);
            else
                $oData = $oHttp->fetch_url($psURL);

            if ($oData)
                $oDB->put($psURL, $oData, true);
        } else {
            if ($pbJson)
                if (gettype($oData) === "string")
                    $oData = json_decode($oData);
        }

        //cTracing::leave();
        return $oData;
    }
}

cCachedHttp::init_obj_store_db();
