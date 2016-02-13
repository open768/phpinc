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

class cRoverImageDetails{
	public $source = null;
	public $thumbnail = null;
	public $image = null;
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

	//*****************************************************************************
	public static function getDetails($psAbbr){
		self::getInstruments();
		return  self::$instrument_map[$psAbbr];
	}
}

//#####################################################################
//#####################################################################
class cSpiritRover{
	const BASE_URL = "http://mars.nasa.gov/mer/gallery/all/";
	const MANIFEST_URL = "spirit.html";
	const USE_CURL = false;
	const MANIFEST_PATH = "[manifest]";
	const DETAILS_PATH = "[details]";
	const MANIFEST_FILE = "manifest";
	const SOLS_FILE = "sols";

	//#####################################################################
	//# PUBLIC functions
	//#####################################################################
	public static function get_sol($piSol){
		//---------if its in the objstore return it
		$oSol =  cObjStore::get_file( self::MANIFEST_PATH, $piSol);
		if ($oSol) return $oSol;
		
		//------------------------------------------------------
		$oManifest = self::pr__get_manifest();
		$aSols = $oManifest->sols;
		if (!array_key_exists((string)$piSol, $aSols)) cDebug::error("Sol $piSol not found");
		$oSol = $aSols[(string)$piSol];

		//------------------------------------------------------
		cObjStore::put_file( self::MANIFEST_PATH, $piSol, $oSol);
		return $oSol;
	}

	//*****************************************************************************
	public static function get_sols(){
		//---------if its in the objstore return it
		$aSols =  cObjStore::get_file( self::MANIFEST_PATH, self::SOLS_FILE);
		if ($aSols) return $aSols;
		
		//------------------------------------------------------
		$oManifest = self::pr__get_manifest();
		$aSols = array_keys($oManifest->sols);

		//------------------------------------------------------
		cObjStore::put_file( self::MANIFEST_PATH, self::SOLS_FILE, $aSols);
		return $aSols;
	}
	
	//*****************************************************************************
	public static function get_details($psSol, $psInstr){
		$sPath  = self::DETAILS_PATH."/$psSol";
		$oDetails =  cObjStore::get_file( $sPath, $psInstr);
		if ($oDetails) return $oDetails;
		
		//------------------------------------------------------
		$oDetails = self::pr__get_details($psSol, $psInstr);
		cObjStore::put_file( $sPath, $psInstr, $oDetails);
		return $oDetails;
	}
	
	//#####################################################################
	//# PRIVATE functions
	//#####################################################################
	private static function pr__get_url( $psUrl){
		$oHttp = new cHttp();
		$oHttp->USE_CURL = self::USE_CURL;
		return  $oHttp->fetch_url($psUrl);
	}
	
	//*****************************************************************************
	private static function pr__get_details($psSol, $psInstr){
		//find the url where to get the instrument details from
		$oSol = self::get_sol($psSol);
		$aInstruments = $oSol->instruments;
		if (!array_key_exists( $psInstr, $aInstruments)) cDebug::error("instrument $psInstr doesnt exist for sol $psSol");
		$oInstr = $aInstruments[$psInstr];
		$sFragment = $oInstr->url;
		cDebug::extra_debug("url is $sFragment");
		
		//------------------------------------------------------
		$sHTML = self::pr__get_url(self::BASE_URL.$sFragment);
		cDebug::extra_debug("building query object");
		$oDoc = phpQuery::newDocument($sHTML);
		
		//------------------------------------------------------
		cDebug::extra_debug("querying images");
		$oResults = $oDoc["a:has(img[src$=THM.JPG])"];
		if ($oResults->length == 0) cDebug::error("nothing found");
		cDebug::extra_debug("found  $oResults->length matches");

		$aResults = [];
		$oResults->each( function($oMatch) use (&$aResults){
			$oPQ = pq($oMatch);
			$sDetailFragment = $oPQ->attr("href");	
			cDebug::extra_debug("href url is $sDetailFragment<br>");
			
			$oImages = $oPQ->children('img[src$=THM.JPG]');
			if ($oImages->length == 0) cDebug::error("no images found");				
			cDebug::extra_debug("found $oImages->length images");
			
			$oImg = $oImages->eq(0);
			$sThumbUrl = $oImg->attr('src');
			cDebug::extra_debug("thumbnail  is '$sThumbUrl'<br>");
			
			$oImgUrl = self::pr__get_detail_image($sDetailFragment);
			cDebug::extra_debug("image  '$oImgUrl'");
			
			$oDetail = new cRoverImageDetails;
			$oDetail->source = $sDetailFragment;
			$oDetail->thumbnail = $sThumbUrl;
			$oDetail->image = $oImgUrl;
			$aResults[] = $oDetail;
		});
		
		return $aResults;
	}
	
	//*****************************************************************************
	private static function pr__get_detail_image($psFragmentUrl){
		//------------------------------------------------------
		$sPageUrl = self::BASE_URL.$psFragmentUrl;
		$sHTML = self::pr__get_url($sPageUrl);
		cDebug::extra_debug("building query object");
		$oDoc = phpQuery::newDocument($sHTML);
		
		$oResults = $oDoc["a:contains('View Full Image')"];
		if ($oResults->length == 0) cDebug::error('couldnt find details');
		$sUrl = $oResults->eq(0)->attr('href');

		return dirname($psFragmentUrl)."/$sUrl";
	}
			
	//*****************************************************************************
	private static function pr__get_manifest(){
		
		//---------if its in the objstore return it
		$aManifest = cObjStore::get_file( self::MANIFEST_PATH, self::MANIFEST_FILE);
		if ($aManifest) return $aManifest;
		
		//------------------------------------------------------
		$sHTML = self::pr__get_url(self::BASE_URL.self::MANIFEST_URL);
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
	}}

?>
