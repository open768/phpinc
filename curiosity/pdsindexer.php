<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2014 -2015

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

require_once("$phpinc/ckinc/objstore.php");
require_once("$phpinc/ckinc/indexes.php");
require_once("$phpinc/ckinc/gz.php");
require_once("$phpinc/pds/pdsreader.php");
require_once("$phpinc/curiosity/pds.php");


//##########################################################################
class cCuriosityPdsIndexer{
	const PDS_URL = "http://pds-imaging.jpl.nasa.gov/data/msl";
	const OBJDATA_TOP_FOLDER = "[pds]";
	
	private static $PDS_COL_NAMES = ["PATH_NAME", "FILE_NAME", "MSL:INPUT_PRODUCT_ID", "INSTRUMENT_ID", "PLANET_DAY_NUMBER", "PRODUCT_ID", "IMAGE_TIME"];
	
	//**********************************************************************
	public static function index_everything(){
		for ($i=1; $i<6;$i++){
			//if ($i>1)	self::run_indexer( "MSLMHL_000$i", "EDRINDEX");
			//self::run_indexer( "MSLMRD_000$i", "EDRINDEX");
			self::run_indexer( "MSLMST_000$i", "EDRINDEX");
		}
		
		//self::run_indexer( "MSLNAV_0XXX", "INDEX");
		//self::run_indexer( "MSLNAV_1XXX", "INDEX");
		//self::run_indexer( "MSLHAZ_0XXX", "INDEX");
		//self::run_indexer( "MSLHAZ_1XXX", "INDEX");
		//self::run_indexer( "MSLHAZ_1XXX", "INDEX");
		
		//mosaics are different!
		//self::run_indexer( "MSLMOS_1XXX", "INDEX");
	}
	
	//**********************************************************************
	public static function run_indexer( $psVolume, $psIndex){
		cDebug::write("<b>running indexer</b>");
		//-------------------------------------------------------------------------------
		//get the LBL file to understand how to parse the file 
		// eg http://pds-imaging.jpl.nasa.gov/data/msl/MSLMST_0003/INDEX/EDRINDEX.LBL
		$oLBL = cPDS_Reader::fetch_volume_lbl(self::PDS_URL, $psVolume, $psIndex);
		if (cDebug::$EXTRA_DEBUGGING) $oLBL->__dump();
		
		//-------------------------------------------------------------------------------
		//get the TAB file
		$sTBLFileName = $oLBL->get("^INDEX_TABLE");
		if ($sTBLFileName == null)
			cDebug::error("unable to determine TAB filename - was the LBL Parsed correctly?");
		$sTABUrl = self::PDS_URL."/$psVolume/INDEX/$sTBLFileName";
		$sOutFile = "$psVolume.TAB";
		$sTABFile = cPDS_Reader::fetch_tab($sTABUrl, $sOutFile);
		
		//-------------------------------------------------------------------------------
		//parse the TAB file - cant return them as an array as it kills memory
		cDebug::write("<b>write columns data from tab file</b>");
		cPDS_Reader::write_TAB_columns($oLBL, $sTABFile, self::$PDS_COL_NAMES, "MAST");
		cDebug::write("<b>creating index files</b>");
		self::pr__create_index_files(self::PDS_URL."/$psVolume/", "MAST" , false);
		
		cDebug::write("Done OK");
	}
	
	//**********************************************************************
	private static function pr__create_index_files($psUrlPrefix, $psInstr, $pbReplace){
		
		$iCount = cPDS_Reader::get_tab_data_count($psInstr);
		for ($i=0; $i<=$iCount; $i++){
			$aTabData = cPDS_Reader::get_tab_col_file($psInstr, $i);
			$aData = [];
			cDebug::write("<b>creating index files $psInstr $i</b>");

			//build the index
			foreach ($aTabData as $aLine){
				$sSol = "".(int) $aLine["PLANET_DAY_NUMBER"];
				$sInstr = $aLine["INSTRUMENT_ID"];
				$sMSL_product = $aLine["MSL:INPUT_PRODUCT_ID"];
				$sPDS_product = $aLine["PRODUCT_ID"];
				$sUrl = $psUrlPrefix.$aLine["PATH_NAME"].$aLine["FILE_NAME"];
				$sAcqTime = $aLine["IMAGE_TIME"];
				
				if (!array_key_exists ($sSol, $aData)) $aData[$sSol] = [];
				if (!array_key_exists ($sInstr, $aData[$sSol])) $aData[$sSol][$sInstr] = [];
				$aData[$sSol][$sInstr][$sMSL_product] = ["u"=>$sUrl, "t"=>$sAcqTime];
			}
			
			//write out the files - merge with whats there
			foreach ($aData as  $sSol=>$aSolData)	
				foreach ($aSolData as $sInstr=>$aInstrData){
					$sFilename = cIndexes::get_filename(cIndexes::INSTR_PREFIX, cCuriosityPDS::PDS_SUFFIX);
					$aPDSData = cObjStore::get_file(self::OBJDATA_TOP_FOLDER."/$sSol/$sInstr", $sFilename);				
					if ($aPDSData){  
						//update existing with new data
						foreach ($aData as $sNewKey=>$aNewData)
							$aPDSData[$sNewKey] = $aNewData;
					}else
						$aPDSData = $aInstrData;
					cObjStore::put_file( self::OBJDATA_TOP_FOLDER."/$sSol/$sInstr", $sFilename, $aPDSData);
					cDebug::write("$sSol/$sInstr lines:".count($aPDSData));			
				}
		}
	}
}
?>