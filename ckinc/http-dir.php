<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2013 -2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

require_once("$phpInc/ckinc/debug.php");
require_once("$phpInc/ckinc/cached_http.php");

class cHttpDirectory{
	
	public static function read_dir($sURL){
		cDebug::write("Fetching URL: $sURL");
		$oCache = new cCachedHttp();
		$sHTML = $oCache->getCachedUrl($sURL);
				
		//parse the data - find the table that contains the links
		$oDom = new DOMDocument;
		$oDom->loadHTML($sHTML);
		$oList = $oDom->getElementsByTagName("table");
		if ($oList->length != 1) cDebug::error("unexpected number of nodes: ".$oList->length);
		$oTable  = $oList->item(0);
		
		//get the directory names from the table
		$oList = $oTable->getElementsByTagName("tr");
		$aResults = [];
		for ($iIndex = 0; $iIndex < $oList->length; $iIndex++){
			$oTR = $oList->item($iIndex);
			$oAList = $oTR->getElementsByTagName("a");
			if ($oAList->length == 1){
				$oA = $oAList->item(0);
				$aResults[] = $oA->nodeValue;
			}
		}
		return $aResults;
	}
	
}
