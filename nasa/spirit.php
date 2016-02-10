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
require_once("$phpinc/ckinc/objstore.php");
require_once("$phpinc/phpquery/phpQuery-onefile.php");

//#####################################################################
//#####################################################################
class cRoverManifest{
	public $sols = [];
	
	public function add(  $piSol, $psInstr, $piCount, $psUrl){
		$sKey = (string) $piSol;
		if (!array_key_exists($sKey, $this->sols)) $this->sols[$sKey] = new cRoverManifestSol();
		$oEntry = $this->sols[$sKey];
		$oEntry->add($psInstr, $piCount, $psUrl);
	}
}

class cRoverManifestSol{
	public $instruments = [];
	
	public function add($psInstr, $piCount, $psUrl){
		if (!array_key_exists($psInstr, $this->instruments)) $this->instruments[$psInstr] = new cRoverManifestInstrument();
		$oEntry = $this->instruments[$psInstr];
		$oEntry->count = $piCount;
		$oEntry->url = $psUrl;
	}
}

class cRoverManifestInstrument{
	public $count = -1;
	public $url = null;
}

//#####################################################################
//#####################################################################
class cSpiritInstruments{
	static $Instruments = null;
	static $instrument_map = null;
	
	//********************************************************************
	public static function getInstruments(){
		if (! self::$Instruments){
			// build instrument list
			self::$Instruments = [ 
				["name"=>"FHAZ",	"colour"=>"red",		"abbr"=>"F",	"caption"=>"Front Hazcam"],
				["name"=>"RHAZ",	"colour"=>"green",		"abbr"=>"R",	"caption"=>"Rear Hazcam"],
				["name"=>"NAVCAM",	"colour"=>"steelblue",	"abbr"=>"N",	"caption"=>"Navigation Camera"],
				["name"=>"PANCAM",	"colour"=>"lime",		"abbr"=>"P",	"caption"=>"Panoramic Camera"],
				["name"=>"MI_IM",	"colour"=>"blue",		"abbr"=>"M",	"caption"=>"Microscopic Imager"],
				["name"=>"ENT",		"colour"=>"white",		"abbr"=>"E",	"caption"=>"Entry"],
				["name"=>"DES",		"colour"=>"yellow",		"abbr"=>"D",	"caption"=>"Descent"],
				["name"=>"LAND",	"colour"=>"cyan",		"abbr"=>"L",	"caption"=>"Landing"],
				["name"=>"EDL",		"colour"=>"tomato",		"abbr"=>"EDL",	"caption"=>"Entry, Descent, and Landing"]
			];
			// build associative array
			self::$instrument_map = [];
			foreach (self::$Instruments as $oInstr){
				self::$instrument_map[$oInstr["name"]] = $oInstr;
				self::$instrument_map[$oInstr["abbr"]] = $oInstr;
			}
			
		}
		return self::$Instruments;
	}
	
	//*****************************************************************************
	public static function getAbbrev($psName){
		self::getInstruments();
		if (array_key_exists($psName,self::$instrument_map))
			return self::$instrument_map[$psName]["abbr"];
		
		foreach (self::$Instruments as $aInstrument)
			if ($aInstrument["caption"] == $psName)
				return $aInstrument["abbr"];
			
		cDebug::error("unknown Instrument: $psName");
	}
	
	//*****************************************************************************
	public static function getInstrumentName($psAbbr){
		self::getInstruments();
		return  self::$instrument_map[$psAbbr]["name"];
	}
}

//#####################################################################
//#####################################################################
class cSpiritRover{
	const BASE_URL = "http://mars.nasa.gov/mer/gallery/all/";
	const MANIFEST_URL = "spirit.html";
	const USE_CURL = false;
	const MANIFEST_PATH = "[manifest]";
	const MANIFEST_FILE = "sols";

	//#####################################################################
	//# PUBLIC functions
	//#####################################################################
	public static function get_manifest(){
		
		//---------if its in the objstore return it
		$aManifest = cObjStore::get_file( self::MANIFEST_PATH, self::MANIFEST_FILE);
		if ($aManifest) return $aManifest;
		
		//------------------------------------------------------
		$oHttp = new cCachedHttp();
		$oHttp->USE_CURL = self::USE_CURL;
		$oHttp->CACHE_EXPIRY = cHash::FOREVER;
		$sHTML = $oHttp->getCachedUrl(self::BASE_URL.self::MANIFEST_URL);
		
		//------------------------------------------------------
		cDebug::write("building query object");
		$oDoc = phpQuery::newDocument($sHTML);
		
		//------------------------------------------------------
		$oManifest = new cRoverManifest();
		
		//------------------------------------------------------
		//find all selects with name solfile.
		cDebug::write("locating instruments");
		$oResults = $oDoc["select[name='solFile']"];
		$oResults->each(function($oSelect) use(&$oManifest){
			$oSelectPQ = pq($oSelect);
			
			//get the label
			$sLabel = pq($oSelectPQ)->parent()["label"]->html();
			if (preg_match("/(.*):/", $sLabel, $aMatches))
				$sLabel = $aMatches[1];
			cDebug::write("Instrument found: $sLabel");
			$sAbbr = cSpiritInstruments::getAbbrev($sLabel);
			
			//iterate the Items in the select
			$oSelectPQ["option"]->each( function ($oOption) use (&$oManifest, $sAbbr){
				$oOptionPQ = pq($oOption);
				$sUrl = $oOptionPQ->attr("value");
				
				$sTmp = $oOptionPQ->html();
				preg_match("/Sol (\d+) \((\d+)/", $sTmp, $aMatches);
				$iSol = (int)$aMatches[1];
				$iCount = (int)$aMatches[2];
				$oManifest->add($iSol, $sAbbr, $iCount, $sUrl);
			});
		});
		
		ksort($oManifest->sols);
		
		//------------------------------------------------------
		cObjStore::put_file( self::MANIFEST_PATH, self::MANIFEST_FILE, $oManifest);
		return $oManifest;
	}
	
	//#####################################################################
	//# PRIVATE functions
	//#####################################################################
}

?>
