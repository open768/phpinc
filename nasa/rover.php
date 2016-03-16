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
class cRoverConstants{
	const MANIFEST_PATH = "[manifest]";
	const DETAILS_PATH = "[details]";
	const MANIFEST_FILE = "manifest";
	const SOLS_FILE = "sols";
}

//#####################################################################
//#####################################################################
class cRoverSols{
	private $aSols = null;
	
	public function add($piSol, $psInstr, $piCount, $psUrl){
		if (!$this->aSols) $this->aSols = [];
		
		$sKey = (string) $piSol;
		if (!array_key_exists($sKey, $this->aSols)) $this->aSols[$sKey] = new cRoverSol();
		$oSol = $this->aSols[$sKey];
		$oSol->add($psInstr, $piCount, $psUrl);
	}	

	public function get_sol_numbers	(){
		if (!$this->aSols) cDebug::error("no sols loaded");
		ksort($this->aSols);
		return array_keys($this->aSols);
	}
	
	public function get_sol($piSol){
		if (!array_key_exists((string)$piSol, $this->aSols)) cDebug::error("Sol $piSol not found");
		return $this->aSols[(string)$piSol];
	}
}

//#####################################################################
//#####################################################################
abstract class cRoverManifest{
	private $oSols = null;
	protected abstract function pr_generate_manifest();
	protected abstract function pr_generate_details($psSol, $psInstr);
	
	//*************************************************************************************************
	public function get_details($psSol, $psInstr){
		$sPath  = cRoverConstants::DETAILS_PATH."/$psSol";
		$oDetails =  cObjStore::get_file( $sPath, $psInstr);
		if ($oDetails) return $oDetails;
		
		//------------------------------------------------------
		$oDetails = $this->pr_generate_details($psSol, $psInstr);
		cObjStore::put_file( $sPath, $psInstr, $oDetails);
		return $oDetails;
	}
	
	//*************************************************************************************************
	function __construct() {
		$this->oSols = cObjStore::get_file( cRoverConstants::MANIFEST_PATH, cRoverConstants::MANIFEST_FILE);
		if (!$this->oSols){
			$this->pr_generate_manifest();
			cObjStore::put_file( cRoverConstants::MANIFEST_PATH, cRoverConstants::MANIFEST_FILE, $this->oSols);
		}
	}
		
	//*************************************************************************************************
	public function add(  $piSol, $psInstr, $piCount, $psUrl){
		if (!$this->oSols) $this->oSols = new cRoverSols();
		$this->oSols->add(  $piSol, $psInstr, $piCount, $psUrl);
	}
	
	//*************************************************************************************************
	public function get_sol_numbers(){
		if (!$this->oSols) cDebug::error("no sols");
		return $this->oSols->get_sol_numbers();
	}
	
	//*************************************************************************************************
	public function get_sol($piSol){
		if (!$this->oSols) cDebug::error("no sols");
		return $this->oSols->get_sol($piSol);
	}
}

//#####################################################################
//#####################################################################
class cRoverSol{
	public $instruments = [];
	
	public function add($psInstr, $piCount, $psUrl){
		if (!array_key_exists($psInstr, $this->instruments)) $this->instruments[$psInstr] = new cRoverInstrument();
		$oEntry = $this->instruments[$psInstr];
		$oEntry->count = $piCount;
		$oEntry->url = $psUrl;
	}
}

//#####################################################################
//#####################################################################
class cRoverInstrument{
	public $count = -1;
	public $url = null;
}

//#####################################################################
//#####################################################################
class cRoverImage	{
	public $source = null;
	public $thumbnail = null;
	public $image = null;
}
?>
