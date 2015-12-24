<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2014 -2015

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/cached_http.php");

class cNasaOpenMarsAPI{
	public static $API_KEY = null;
	const BASE_URL = "https://api.nasa.gov/mars-photos/api/v1/rovers";
	const API_KEY_FIELD = "api_key";
	public static $HTTPS_CERT_FILENAME = null;

	public static function get_missions(){
		$sUrl = self::BASE_URL . self::pr__KEY_Querystring("?");
		cDebug::write("getting url:$sUrl");
		
		$oCache = new cCachedHttp();
		$oCache->USE_CURL = false;
		$oCache->HTTPS_CERT_FILENAME = self::$HTTPS_CERT_FILENAME;
		$oData = $oCache->getCachedJson($sUrl);
		cDebug::write("got something for $sUrl");

		return $oData;
	}

	private static function pr__KEY_Querystring($psPrefix){
		if (!self::$API_KEY){
				cDebug::error("API key not set in cNasaOpenMarsAPI");
		}
		
		return ( $psPrefix.self::API_KEY_FIELD."=".self::$API_KEY);
	}
}

?>
