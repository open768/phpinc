<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2016

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED

uses phpQuery https://code.google.com/archive/p/phpquery/ which is Licensed under the MIT license

**************************************************************************/
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/cached_http.php");
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/phpquery/phpQuery-onefile.php");

class cInstrumentManifest{
	public $longName = null;
	public $sols = [];
}
class cSolManifestEntry{
	public $count;
	public $sol;
	public $url;
}

//#####################################################################
//#####################################################################
class cSpiritRover{
	const BASE_URL = "http://mars.nasa.gov/mer/gallery/all/";
	const MANIFEST_URL = "spirit.html";
	const USE_CURL = false;

	//#####################################################################
	//# PUBLIC functions
	//#####################################################################
	public static function get_manifest(){
		$aManifest = [];
		
		//------------------------------------------------------
		$oHttp = new cCachedHttp();
		$oHttp->USE_CURL = self::USE_CURL;
		$oHttp->CACHE_EXPIRY = cHash::FOREVER;
		$sHTML = $oHttp->getCachedUrl(self::BASE_URL.self::MANIFEST_URL);
		
		//------------------------------------------------------
		cDebug::write("building query object");
		$oDoc = phpQuery::newDocument($sHTML);
		
		//------------------------------------------------------
		cDebug::write("locating instruments");
		//find all selects with name solfile.
		$oResults = $oDoc["select[name='solFile']"];
		$oResults->each(function($oSelect) use(&$aManifest){
			$oInstrument = new cInstrumentManifest();
			$oSelectPQ = pq($oSelect);
			
			//get the label
			$sLabel = pq($oSelectPQ)->parent()["label"]->html();
			cDebug::write("Instrument found: $sLabel");
			$oInstrument->longName = $sLabel;
			
			//iterate the Items in the select
			$oSelectPQ["option"]->each( function ($oOption) use (&$oInstrument){
				$oEntry = new cSolManifestEntry();
				$oOptionPQ = pq($oOption);
				$oEntry->url = $oOptionPQ->attr("value");
				
				$sTmp = $oOptionPQ->html();
				preg_match("/Sol (\d+) \((\d+)/", $sTmp, $aMatches);
				$iSol = (int)$aMatches[1];
				$oEntry->sol = $iSol;
				$oEntry->count = (int)$aMatches[2];
				
				$oInstrument->sols[$iSol]=$oEntry;
			});
			
			// add instrument manifest to manifest
			$aManifest[] = $oInstrument;
		});
		
		//------------------------------------------------------
		return json_encode($aManifest);
	}
	
	public static function get_sol(){
	}
}

?>
