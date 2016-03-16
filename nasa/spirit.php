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
require_once("$phpinc/nasa/rover.php");

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
class cSpiritRover extends cRoverManifest{
	const BASE_URL = "http://mars.nasa.gov/mer/gallery/all/";
	const MANIFEST_URL = "spirit.html";
	const USE_CURL = false;

	//#####################################################################
	//# PRIVATE functions
	//#####################################################################
	private static function pr__get_url( $psUrl){
		$oHttp = new cHttp();
		$oHttp->USE_CURL = self::USE_CURL;
		return  $oHttp->fetch_url($psUrl);
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

	//#####################################################################
	//# implement abstract functions
	//#####################################################################
	protected function pr_generate_details($psSol, $psInstr){
		//find the url where to get the instrument details from
		$oSol = $this->get_sol($psSol);
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
			
			$oDetail = new cRoverImage;
			$oDetail->source = $sDetailFragment;
			$oDetail->thumbnail = $sThumbUrl;
			$oDetail->image = $oImgUrl;
			$aResults[] = $oDetail;
		});
		
		return $aResults;
	}
			
	//*****************************************************************************
	protected function pr_generate_manifest(){
		
		//------------------------------------------------------
		cDebug::write("fetching page from NASA");
		$sHTML = self::pr__get_url(self::BASE_URL.self::MANIFEST_URL);
		cDebug::write("building query object");
		$oDoc = phpQuery::newDocument($sHTML);
		
		//------------------------------------------------------
		//find all selects with name solfile.
		cDebug::write("locating instruments");
		$oResults = $oDoc["select[name='solFile']"];
		$oParent = $this;
		
		$oResults->each(function($oSelect) use(&$oParent){
			$oSelectPQ = pq($oSelect);
			
			//get the label
			$sLabel = pq($oSelectPQ)->parent()["label"]->html();
			if (preg_match("/(.*):/", $sLabel, $aMatches))
				$sLabel = $aMatches[1];
			cDebug::write("Instrument found: $sLabel");
			$sAbbr = cSpiritInstruments::getAbbrev($sLabel);
			
			//iterate the Items in the select
			$oSelectPQ["option"]->each( function ($oOption) use (&$oParent, $sAbbr){
				$oOptionPQ = pq($oOption);
				$sUrl = $oOptionPQ->attr("value");
				
				$sTmp = $oOptionPQ->html();
				preg_match("/Sol (\d+) \((\d+)/", $sTmp, $aMatches);
				$iSol = (int)$aMatches[1];
				$iCount = (int)$aMatches[2];
				$oParent->add($iSol, $sAbbr, $iCount, $sUrl);
			});
		});
	}
}

?>
