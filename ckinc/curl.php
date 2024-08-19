<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 -2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/
class cCurl {
    private $oCurl = null;
    private $url = null;

    function __construct($psUrl) {
        $this->url = $psUrl;
        $this->oCurl = curl_init($psUrl);
        $this->setopt(CURLOPT_URL, $psUrl);
    }

    public function setopt($piThing, $pValue) {
        //cDebug::extra_debug("setting curl opt $piThing");
        try {
            curl_setopt($this->oCurl, $piThing, $pValue);
        } catch (Exception $e) {
            cDebug::extra_debug("oops");
            cDebug::stacktrace();
            throw $e;
        }
    }

    public function exec() {
        $sUrl = $this->url;
        cDebug::extra_debug("curl getting: $sUrl");
        $response = curl_exec($this->oCurl);
        $iErr = curl_errno($this->oCurl);
        if ($iErr != 0) {
            print curl_error($this->oCurl) . "<p>";
            curl_close($this->oCurl);
            cDebug::error("ERROR URL was: $sUrl <p>");
        } else {
            cDebug::extra_debug("no error reported by Curl");
            curl_close($this->oCurl);
        }
        return $response;
    }
}
