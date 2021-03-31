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
require_once("$phpinc/ckinc/objstoredb.php");

class cCachedHttp{
	const INFINITE = -1;
	private static $oObjStore = null;
	public $CACHE_EXPIRY = 3600;  //(seconds)
	public $USE_CURL = true;
	public $ALLOW_SELF_SIGNED_CERT = true;
	private $oCache = null;
	private $sCacheFile = null;
	public 	$fileHashing = true;
	public 	$show_progress = false;
	public  $HTTPS_CERT_FILENAME = null;

	//********************************************************************
	public static function pr_init_objstore(){
		if (!self::$oObjStore){
			$oObjStore = new cObjStoreDB();
			$oObjStore->realm = "CACHTTP";
			$oObjStore->check_expiry = true;
			$oObjStore->expire_time = 3600;
			$oObjStore->set_table("HTMLCAC");
			
			self::$oObjStore = $oObjStore;
		}
	}
	//*****************************************************************************
	public function deleteCachedURL($psURL){
		cHash::delete($psURL);
	}
	
	//*****************************************************************************
	public function getCachedUrl($psURL){	
		cDebug::enter();
		$oResult= $this->pr_do_get($psURL, false);
		cDebug::leave();
		return $oResult;
	}

	//*****************************************************************************
	public function getXML($psURL){
		cDebug::enter();
		
		cDebug::write("Getting XML from: $psURL");
		$sXML = $this->getCachedUrl($psURL);
		cDebug::write("converting string to XML: ");
		$oXML = simplexml_load_string($sXML);
		cDebug::write("finished conversion");
		cDebug::leave();
		return $oXML;
	}
	
	//*****************************************************************************
	public function getCachedUrltoFile($psURL){	//for large files too big for sql
		cDebug::enter();
		$sHash = cHash::hash($psURL);
		cHash::$CACHE_EXPIRY = $this->$CACHE_EXPIRY;		//dangerous fudge
		$sPath = cHash::getPath($sHash);
		
		if (! cHash::exists($sHash)){
			cHash::make_hash_folder( $sHash);
			$oHttp = new cHttp();
			$oHttp->ALLOW_SELF_SIGNED_CERT = $this->ALLOW_SELF_SIGNED_CERT;
			$oHttp->show_progress = true;
			$oHttp->fetch_to_file($psURL, $sPath, true, 60, true);
		}
		cDebug::leave();
		return $sPath;
	}

	//*****************************************************************************
	public function getCachedJson($psURL){	
		cDebug::enter();
		$oResult=$this->pr_do_get($psURL, true);
		cDebug::leave();
		return $oResult;
		
	}
	
	//*****************************************************************************
	//*
	//*****************************************************************************
	private function pr_do_get($psURL, $pbJson){
		cDebug::enter();

		$oHttp = new cHttp();
		$oHttp->USE_CURL = $this->USE_CURL;
		$oHttp->ALLOW_SELF_SIGNED_CERT = $this->ALLOW_SELF_SIGNED_CERT;
		$oHttp->show_progress = $this->show_progress;
		$oHttp->HTTPS_CERT_FILENAME = $this->HTTPS_CERT_FILENAME;
		
		$oResponse = null;
		cDebug::write("getting url:$psURL");
		if (cHash::exists($psURL, true)) cHash::delete($psURL); //remove the old chash cached item
		
		$oData = self::$oObjStore->get($psURL,true);
		if ($oData == null){
			cDebug::extra_debug("obj not cached $psURL");
			if ($pbJson)
				$oData = $oHttp->getJson($psURL);
			else
				$oData = $oHttp->fetch_url($psURL);
				
			if ($oData) 
				self::$oObjStore->put($psURL, $oData, true);
		}
		
		cDebug::leave();
		return $oData;
	}
}

cCachedHttp::pr_init_objstore();

?>