<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2014 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/objstore.php");
require_once("$phpinc/ckinc/gz.php");
require_once("$root/php/static/static.php");
require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/pds/lbl.php");


class cPDS_Reader{
	public static $force_delete = false;
	public static $columns_object_name = "INDEX_TABLE";
	const MAX_TAB_LINES = "10000";
	const TAB_FOLDER = "[PDSTAB]";
	const TAB_ID="[PDSTAB]";
	
	//**********************************************************************
	public static function fetch_volume_lbl( $psBaseUrl, $psVolume, $psIndex){
		$sLBLUrl = $psBaseUrl."/$psVolume/INDEX/$psIndex.LBL";
		$sOutFile = "$psVolume.LBL";
		
		cDebug::write("fetching volume LBL $sLBLUrl");
		$oHttp = new cHttp();
		$sFile = $oHttp->large_url_path($sOutFile);
		if (!file_exists("$sFile.gz")){
			cDebug::write("$sFile.gz doesnt exist" );
			
			$sLBLFile = $oHttp->fetch_large_url($sLBLUrl, $sOutFile, false);
			cDebug::write("fetched to $sLBLFile" );
			
			cDebug::write("compressing $sLBLFile");
			cGzip::compress_file($sLBLFile);
			cDebug::write("output filename is $sLBLFile");
		}
		
		//------------------------------------------------------------------
		//parse the lbl file
		return self::parse_LBL("$sFile.gz");
	}
	
	//**********************************************************************
	public static function fetch_lbl( $psUrl){
		global $root;
		//create a unique hash for the 
		cHash::$CACHE_EXPIRY = cHash::FOREVER;		//cache forever
		//cHash::$show_filenames = true;

		$sHashUrl = cHash::hash($psUrl);
		$sHashLBL = cHash::hash("PDSOBJ-$psUrl");
		
		if (self::$force_delete){
			cDebug::write("deleting cached file for $psUrl");
			cHash::delete_hash($sHashLBL);
			self::$force_delete = false;
		}

		
		if (!cHash::exists($sHashLBL)){
			//--- fetch the raw LBL file
			$sFolder = cHash::make_hash_folder($sHashUrl);
			$sUrlFilename = cHash::getPath($sHashUrl);
			$oHttp = new cHttp();
			$oHttp->fetch_to_file($psUrl, $sUrlFilename, false);
			
			//---parse into a LBL obj
			cDebug::write("Parsing http");
			$oLBL = new cPDS_LBL();
			$oLBL->parseFile($sUrlFilename);
			
			//--- store LBL obj
			cHash::put_obj($sHashLBL, $oLBL);
			
			//--- delete url hash
			unlink($sUrlFilename);
		}else{
			cDebug::write("file exists on disk $sHashLBL");
			$oLBL = cHash::get_obj($sHashLBL);
		}
		//$oLBL->__dump();
		return $oLBL;
	}
	
	
	//**********************************************************************
	public static function parse_LBL( $psFilename){
		$oLBL = new cPDS_LBL();
		$oLBL->parseFile($psFilename);
		cDebug::write("parse file OK");
		return $oLBL;
	}
	
	//**********************************************************************
	public static function fetch_tab( $psUrl, $psOutFile){
		$oHttp = new cHttp();
		$sFile = $oHttp->large_url_path($psOutFile);
		if (!file_exists("$sFile.gz")){
			$sFile = $oHttp->fetch_large_url($psUrl, $psOutFile, false);
			cGzip::compress_file($sFile);
		}else
			cDebug::write("file exists on disk $sFile.gz");

		return "$sFile.gz";
	}
	
	//**********************************************************************
	private static function pr__write_TAB_columns($psID, $piTabIndex, $paData){
		cDebug::write("writing out tab file $piTabIndex");
		$sFolder = self::TAB_ID.$psID.$piTabIndex;
		$sHash = cHash::hash($sFolder);
		cHash::put_obj($sHash, $paData, true);
	}
	
	//**********************************************************************
	public static function get_TAB_columns( $poLBL, $psTabFile, $aColNames, $psID){
		$iTabIndex=0;
		$iTabLine=0;
		
		// get the columns to be used for indexing
		$aCols = self::pr__get_tab_columns($poLBL, $aColNames);
		set_time_limit( 30 );
		
		//open the tab file
		$aOut = [];
		$iCount = 0;
		try{
			$fHandle = gzopen($psTabFile, 'rb');
			while(!gzeof($fHandle)){
				$sLine = gzgets($fHandle);
				if (trim($sLine) == "") continue;
				$aLine = self::pr__extract_tab_line($sLine, $aCols);
				$aOut[] = $aLine;
				
				//chunk the data
				$iTabLine++;
				if ($iTabLine >= self::MAX_TAB_LINES){
					self::pr__write_TAB_columns($psID,$iTabIndex, $aOut);
					cDebug::write("processed $iCount lines");
					unset($aOut);
					$aOut=[];
					gc_collect_cycles();
					$iTabLine=0;
					$iTabIndex++;
					set_time_limit( 30 );
				}
				$iCount ++;
			}
			
			self::pr__write_TAB_columns($psID,$iTabIndex, $aOut);
			
		}catch (Exception $e){
			cDebug::error ($e->getMessage());
		}
		gzclose($fHandle);
		cDebug::write("finishing get_TAB_columns");
		
		cDebug::write("Processed $iCount lines -  into $iTabIndex files");
		cObjStore::put_file(self::TAB_FOLDER, $psID, $iTabIndex);

		return $iTabIndex;
	}
	
	//**********************************************************************
	public static function get_tab_col_count($psID){
		return cObjStore::get_file(self::TAB_FOLDER, $psID);
	}
	
	//**********************************************************************
	public static function delete_tab_col_files($psID){
		$iCount = self::get_tab_col_count($psID);
		if ($iCount == null) return;
		
		for ($i=0; $i <= $iCount; $i++){
			$sFolder = self::TAB_ID.$psID.$i;
			$sHash = cHash::hash($sFolder);
			cHash::delete_hash($sHash);
		}
		cObjStore::kill_file(self::TAB_FOLDER, $psID);

	}

	//**********************************************************************
	public static function get_tab_col_file($psID, $piIndex){
		$sFolder = self::TAB_ID.$psID.$piIndex;
		$sHash = cHash::hash($sFolder);
		return cHash::get_obj($sHash);
	}
	
	//######################################################################
	//# PRIVATES
	//######################################################################
	private static function pr__get_tab_columns($poLBL, $paColNames){
		$aResult = [];
		//get the column names of interest
		$oINDEXLBL = $poLBL->get(self::$columns_object_name);
		if (!$oINDEXLBL){
			//cDebug::write("column names:");
			$poLBL->__dump();
			cDebug::error("couldnt find column ". self::$columns_object_name);
			return;
		}
		
		//$oINDEXLBL->dump_array("COLUMN", "NAME");
		$aCols = $oINDEXLBL->get("COLUMN");
		
		foreach ($paColNames as $sName){
			foreach ($aCols as $oCol)
				if ($oCol->get("NAME") === $sName){
					$aResult[$sName] = $oCol;
					continue;
				}
		}
		return $aResult;
	}
	
	//**********************************************************************
	private static function pr__extract_tab_line($psLine, $paCols){
		$aOut = [];
		foreach ($paCols as $sName=>$oColLBL){
			$iStart = $oColLBL->get("START_BYTE");
			$iCount = $oColLBL->get("BYTES");
			
			$sExtract = substr($psLine, $iStart-1, $iCount);
			$aOut[$sName] = trim($sExtract);
		}
		return $aOut;
	}
}
?>