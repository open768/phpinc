<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2014 -2015

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/hash.php");

class cCachedHttp{
	public $CACHE_EXPIRY = 3600;  //(seconds)
	public $USE_CURL = true;
	private $oCache = null;
	private $sCacheFile = null;
	public 	$fileHashing = true;
	public 	$show_progress = false;
	public  $HTTPS_CERT_FILENAME = null;



	//*****************************************************************************
	public function deleteCachedURL($psURL){
		$sHash = cHash::hash($psURL);
		cHash::delete_hash($sHash);
	}
	
	//*****************************************************************************
	public function getCachedUrl($psURL){	
		return $this->pr_do_get($psURL, false);
	}

	//*****************************************************************************
	public function getXML($psURL){
		cDebug::write("Getting XML from: $psURL");
		$sXML = $this->getCachedUrl($psURL);
		cDebug::write("converting string to XML: ");
		$oXML = simplexml_load_string($sXML);
		cDebug::write("finished conversion");
		return $oXML;
	}
	
	//*****************************************************************************
	public function getCachedUrltoFile($psURL){	
		$sHash = cHash::hash($psURL);
		cHash::$CACHE_EXPIRY = $this->$CACHE_EXPIRY;		
		$sPath = cHash::getPath($sHash);
		
		if (! cHash::exists($sHash)){
			cHash::make_hash_folder( $sHash);
			$oHttp = new cHttp();
			$oHttp->show_progress = true;
			$oHttp->fetch_to_file($psURL, $sPath, true, 60, true);
		}
		return $sPath;
	}

	//*****************************************************************************
	public function getCachedJson($psURL){	
		return $this->pr_do_get($psURL, true);
	}
	
	//*****************************************************************************
	//*
	//*****************************************************************************
	private function pr_do_get($psURL, $pbJson){

		$oHttp = new cHttp();
		$oHttp->USE_CURL = $this->USE_CURL;
		$oHttp->show_progress = $this->show_progress;
		$oHttp->HTTPS_CERT_FILENAME = $this->HTTPS_CERT_FILENAME;
		
		$oResponse = null;
		cDebug::write("getting url:$psURL");
		
		$sHash = cHash::hash($psURL);
		cHash::$CACHE_EXPIRY = $this->CACHE_EXPIRY;
		if (cHash::exists($sHash)){
			cDebug::extra_debug("cached: $sHash");
			$oResponse = cHash::get_obj($sHash);
		}else{
			cDebug::extra_debug("not cached");
			if ($pbJson)
				$oResponse = $oHttp->getJson($psURL);
			else
				$oResponse = $oHttp->fetch_url($psURL);
				
			if ($oResponse) 
				cHash::put_obj($sHash, $oResponse, true);
		}
		
		return $oResponse;
	}

}
?>