<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 -2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/
require_once  cAppGlobals::$ckPhpInc . "/curl.php";

class cHttpTuple {
    public $key;
    public $value;
}

class cHttp {
    public $LARGE_URL_DIR = "[cache]/[Largeurls]";
    public $progress_len = 0;
    public $progress_count = 0;
    public $show_progress = false;
    public $debug = false;
    public $HTTPS_CERT_FILENAME = null;
    public $USE_CURL = true;
    public $extra_headers = null;
    private $authenticate = false;
    private $username = null;
    private $password = null;
    public $response_headers = [];
    public $request_payload = null;
    public $ALLOW_SELF_SIGNED_CERT = true;
    public $method = null;

    //*****************************************************************************
    public function set_credentials($psUserName, $psPassword) {
        $this->authenticate = true;
        $this->username = $psUserName;
        $this->password = $psPassword;
    }

    //*****************************************************************************
    public function getJson($psURL) {
        $sResponse = $this->fetch_url($psURL);
        //if ($this->debug) cDebug::write("response was : $sResponse");
        $oData = json_decode($sResponse);
        //if ($this->debug) cDebug::vardump($oData);
        return $oData;
    }

    //*****************************************************************************
    public  function fetch_image($psUrl) {
        if ($this->USE_CURL)
            return $this->pr__fetch_curl_image($psUrl);
        else
            cDebug::error("non Curl fetch image not implemented");
    }

    //*****************************************************************************
    public  function fetch_url($psUrl) {
        if ($this->debug) cTracing::enter();
        if ($this->debug) cDebug::extra_debug($psUrl);

        if ($this->USE_CURL) {
            $sHTML = $this->pr__fetch_curl_url($psUrl);
        } else
            $sHTML = $this->pr__fetch_basic_url($psUrl);

        if ($this->debug) cTracing::leave();
        return $sHTML;
    }

    //*****************************************************************************
    public function fetch_to_file($psUrl, $psPath, $pbOverwrite = false, $piTimeOut = 60, $pbGzip = false) {
        //cTracing::enter();
        if ($this->USE_CURL) {
            cDebug::error("curl is not supported");
            return $this->pr__fetch_curl_to_file($psUrl, $psPath, $pbOverwrite, $piTimeOut, $pbGzip);
        } else
            cDebug::error("non Curl fetch file not implemented");
        //cTracing::leave();
    }

    //*****************************************************************************
    public function large_url_path($psFilename) {


        $sDir = cAppGlobals::$root . "/" . $this->LARGE_URL_DIR;
        return "$sDir/$psFilename";
    }

    //*****************************************************************************
    public function fetch_large_url($psUrl, $psFilename, $pbOverwrite = false) {

        //cTracing::enter();

        //check the folder is there
        $sDir = cAppGlobals::$root . "/" . $this->LARGE_URL_DIR;
        if (!is_dir($sDir)) {
            cDebug::write("making cache dir $sDir");
            mkdir($sDir, 0700, true);
        }

        $sPath = $this->large_url_path($psFilename);
        $this->show_progress = true;
        $sFilename = $this->fetch_to_file($psUrl, $sPath, $pbOverwrite, 600);
        //cTracing::leave();
        return $sFilename;
    }

    //############################################################################
    //#
    //############################################################################
    private function pr__make_header_string($aHeaders) {
        if ($this->debug) cTracing::enter();

        $sOut = null;
        if (!is_array($aHeaders))
            cDebug::error("extra headers must be an array");
        else {
            $bFirst = true;
            foreach ($aHeaders as $sKey => $sValue) {
                if (!$bFirst) $sOut .= "\r\n";
                $sOut .= "$sKey: $sValue";
                $bFirst = false;
            }
        }
        if ($this->debug) cTracing::leave();
        return $sOut;
    }

    //********************************************************************
    private function pr__fetch_basic_url($psUrl, $pbAllowNull = true) {
        global $http_response_header;  //PHP global set by file_get_contents

        if ($this->debug) cTracing::enter();
        $sHTML = null;

        $oContext = null;
        $this->response_headers = [];
        $aContext = ["ssl" => ["verify_peer" => false, "verify_peer_name" => false]];
        $aHeaders = [];
        $aHttpContext = [];

        //-----------------------------------------------------------------------
        if ($this->authenticate) {
            $sCredentials = base64_encode("$this->username:$this->password");
            $aHeaders["Authorization"] =  "Basic $sCredentials";
        }
        if ($this->extra_headers)
            $aHeaders = array_merge($aHeaders, $this->extra_headers);

        //********************************************************************
        if ($this->request_payload !== null) {
            if (!array_key_exists("Content-Type", $aHeaders))
                $aHeaders["Content-Type"] = "application/json";
            $aHeaders["Content-Length"] = strlen($this->request_payload);
            $aHttpContext["content"] = $this->request_payload;
            $aHttpContext["method"]  = "POST";
        }
        if ($this->method !== null)
            $aHttpContext["method"]  = $this->method;
        $aHeaders["User-Agent"] = "ChickenKatsu.co.uk/1.0";

        //********************************************************************
        $aHttpContext["header"] =  $this->pr__make_header_string($aHeaders);
        $aHttpContext["ignore_errors"] =  true;            //catches response even with error 500
        $aContext["http"] = $aHttpContext;
        $oContext = stream_context_create($aContext);
        if ($this->debug) cDebug::vardump($aContext);


        //********************************************************************
        try {
            $sHTML = @file_get_contents($psUrl, false, $oContext);
        } catch (Exception $e) {
            cDebug::error("couldnt get url $psUrl : $e");
        }

        //********************************************************************
        if (!strpos($http_response_header[0], "200")) {
            cDebug::vardump($http_response_header);
            cDebug::write($sHTML);
            cDebug::error($http_response_header[0]);
        } else {
            foreach ($http_response_header as $line)
                if (preg_match("/^(.*?)\:\s*(\w*.*?)(\;|$)/", $line, $aMatches)) {
                    $oTuple = new cHttpTuple();
                    $oTuple->key = $aMatches[1];
                    $oTuple->value = $aMatches[2];
                    array_push($this->response_headers, $oTuple);
                }
        }

        if ($sHTML == null && !$pbAllowNull)
            cDebug::error("null response received : ");

        if ($this->debug) cTracing::leave();
        return $sHTML;
    }

    //############################################################################
    //#  URL Building functions
    //############################################################################
    public static function build_qs_from_array(string $psBase, array $paAssoc) {
        $sQS = $psBase;
        foreach ($paAssoc as $sKey => $sValue)
            $sQS = self::build_qs($sQS, $sKey, $sValue);
        return $sQS;
    }

    //******************************************************************************
    public static function build_qs(string $psBase, string $psQueryParam, $psQueryValue = null) {
        if ($psQueryParam == "") return $psBase;

        $sUrl = $psBase;
        if (!strpos($sUrl, "?"))
            $sUrl .= "?";
        else
            $sUrl .= "&";

        $sUrl .= $psQueryParam;
        if ($psQueryValue !== null)
            $sUrl .= "=" . urlencode($psQueryValue);

        return $sUrl;
    }

    //******************************************************************************
    public static function build_url(string $psBase, $psQueryParam, $psQueryValue = null) {
        if (cCommon::is_string_empty($psBase)) cDebug::error("base url cant be empty");
        $sUrl = self::build_qs($psBase, $psQueryParam, $psQueryValue);

        return $sUrl;
    }

    //*******************************************************************
    public static function page_matches($psUrl) {
        $sPageUrl = cHeader::get_page_url();

        $aCurrent = (object) parse_url($sPageUrl);
        $aInput = (object) parse_url($psUrl);

        //fudged for reverse proxy
        return (strpos($aInput->path, $aCurrent->path) >= 0);
    }

    //############################################################################
    //#
    //#  CURL functions
    //#
    //############################################################################
    private function pr__curl_init($psUrl) {
        //cTracing::enter();


        $oCurl = new cCurl($psUrl);
        $oCurl->setopt(CURLOPT_URL, $psUrl);
        $oCurl->setopt(CURLOPT_FAILONERROR, 1);
        $oCurl->setopt(CURLOPT_RETURNTRANSFER, 1);
        $oCurl->setopt(CURLOPT_FOLLOWLOCATION, true);
        if (cDebug::is_extra_debugging()) {
            cDebug::extra_debug("enabling CURL_verbosity");
            $oCurl->setopt(CURLOPT_VERBOSE, 1);
        }
        if ($this->ALLOW_SELF_SIGNED_CERT) {
            cDebug::extra_debug("allowing self signed certs");
            $oCurl->setopt(CURLOPT_SSL_VERIFYPEER, 0);
        }

        //use gzip compression to save bandwidth
        $oCurl->setopt(CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip,deflate'));
        $oCurl->setopt(CURLOPT_ENCODING, '');         //decode automatically

        //used to show progress
        if ($this->show_progress) {
            $parent = $this;
            $oCurl->setopt(
                CURLOPT_PROGRESSFUNCTION,
                function ($p_Resource, $p_dl_size, $p_dl, $p_ul_size) use (&$parent) {
                    $parent->__progress_callback($p_Resource, $p_dl_size, $p_dl, $p_ul_size);
                }
            );
            $oCurl->setopt(CURLOPT_NOPROGRESS, false); // needed to make progress function work
            $this->show_progress = false;                //cancel show progress, must be set every time
        }

        //https stuff - doesnt work :-( pain in the ass
        if ($this->HTTPS_CERT_FILENAME) {
            if (!file_exists($this->HTTPS_CERT_FILENAME))
                cDebug::error("certificate doesnt exist ");

            $oCurl->setopt(CURLOPT_SSL_VERIFYPEER, true);
            $oCurl->setopt(CURLOPT_SSL_VERIFYHOST, 2);
            $sCertPath = cAppGlobals::$root . "/" . $this->HTTPS_CERT_FILENAME;
            cDebug::extra_debug("set cert to $sCertPath");
            $oCurl->setopt(CURLOPT_CAINFO, $sCertPath);
            $oCurl->setopt(CURLOPT_CAPATH, $sCertPath); //broken
        }

        //cTracing::leave();
        return $oCurl;
    }


    //*****************************************************************************
    private function pr__fetch_curl_image($psUrl) {
        $oCurl = $this->pr__curl_init($psUrl);
        //$oCurl->setopt( CURLOPT_BINARYTRANSFER, 1); // this is deprecated and can be removed #54
        $response = $oCurl->exec();

        $oImage = imagecreatefromstring($response);

        return  $oImage;
    }

    //*****************************************************************************
    private function pr__fetch_curl_url($psUrl) {


        //cTracing::enter();

        $oCurl = $this->pr__curl_init($psUrl);
        return  $oCurl->exec();

        //cTracing::leave();	
    }

    //*****************************************************************************
    private function pr__fetch_curl_to_file($psUrl, $psPath, $pbOverwrite = false, $piTimeOut = 60, $pbGzip = false) {
        //check whether the file exists
        if (!$pbOverwrite && file_exists($psPath)) {
            cDebug::write("file exists $psPath");
            return $psPath;
        }

        //ok get the file
        cDebug::write("getting url: $psUrl ");
        $this->progress_len = 0;
        $this->progress_count = 0;

        $fHandle = fopen($psPath, 'w');
        $oCurl = $this->pr__curl_init($psUrl);
        $oCurl->setopt(CURLOPT_RETURNTRANSFER, 0);
        $oCurl->setopt(CURLOPT_FILE, $fHandle);
        if ($this->ALLOW_SELF_SIGNED_CERT) {
            cDebug::extra_debug("allowing self signed certs");
            $oCurl->setopt(CURLOPT_SSL_VERIFYPEER, 0);
        }


        set_time_limit($piTimeOut);
        try {
            $response = $oCurl->exec();
            fclose($fHandle);
        } catch (Exception $e) {
            unlink($psPath);
            throw $e;
        }

        return $psPath;
    }


    //############################################################################
    //#
    //#  Other  functions
    //#
    //############################################################################
    public function __progress_callback($resource, $dl_size, $dl, $ul_size) {

        $this->progress_count++;
        if ($this->progress_count < 20) return;
        $this->progress_count  = 0;

        $this->progress_len++;
        if ($this->progress_len > 120) {
            $this->progress_len = 0;
            echo "<br>";
        }
        cCommon::flushprint("*");
    }
}
