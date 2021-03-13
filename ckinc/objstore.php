<?php
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//% OBJSTORE - simplistic store objects without a database!
//%
//% Problem - creates thousands files on busy websites that exceed inode quotas.
//%  so reduce this SQLlite3
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

require_once("$phpinc/ckinc/gz.php");
require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/ckinc/objstoredb.php");

cObjStore::$rootFolder= "$root/[objdata]";

//TBD functions to be made non static so that a different realm can be used by different 
class cObjStore{
	public static $rootFolder = "";
	public static $OBJDATA_REALM = null;
	
	//#####################################################################
	//# PRIVATES
	//#####################################################################
	private static function pr_get_folder_path( $psFolder){
		if (self::$OBJDATA_REALM == null)
			cDebug::error("OBJDATA_REALM not set in objstore");
		$sOut = self::$rootFolder."/".self::$OBJDATA_REALM;
		if ($psFolder) $sOut.= "/$psFolder";
		return $sOut;
	}
	
	//#####################################################################
	//# PUBLIC
	//#####################################################################
	public static function kill_folder($psFolder){
		$sPath = self::pr_get_folder_path( $psFolder);
		cDebug::write("killing folder $sPath");
		cCommon::delTree($sPath);
	}
	
	//********************************************************************
	public static function kill_file( $psFolder, $psFile){
		$num_args = func_num_args();
		if ($num_args != 2) cDebug::error("kill_file: incorrect number of arguments - expected 2 got $num_args ");

		$folder = self::pr_get_folder_path( $psFolder);
		$file = "$folder/$psFile";
		if (file_exists($file)){
			unlink($file);
			cDebug::write("deleted file $file");
		}
	}
	
	//********************************************************************
	//TODO migrate to using objstore
	public static function get_file( $psFolder, $psFile){
		$aData = null;
		cDebug::enter();
		
		$num_args = func_num_args();
		if ($num_args != 2) cDebug::error("get_file: incorrect number of arguments - expected 2 got $num_args ");
		
		$sFolder = self::pr_get_folder_path( $psFolder);
		$sFile = "$sFolder/$psFile";

		if (file_exists($sFile)){
			$aData = cGzip::readObj($sFile);
			//TBD write to objstoreDB and kill_file
		}elseif( cHash::exists($sFile)){
			cDebug::write("found in hash");
			$aData = cHash::get($sFile);
			//TDB write to objstoreDB and kill hash
		}else{
			//TBD get from objstoreDB
		}

		
		cDebug::leave();
		return $aData;
	}
	
	//********************************************************************
	public static function put_file( $psFolder, $psFile, $poData){
			
		$num_args = func_num_args();
		if ($num_args != 3) cDebug::error("put_file: incorrect number of arguments - expected 3 got $num_args ");
		
		//check that there is something to write
		if ($poData == null) cDebug::error("put_file: no data to write");
		
		$sFolder = self::pr_get_folder_path( $psFolder);
		$sFile = "$sFolder/$psFile";
		cDebug::write("writing to: $sFile");
		cGzip::writeObj($sFile, $poData);
	}
	
	//********************************************************************
	static function push_to_array( $psFolder, $psFile, $poData){
		//always get the latest file
		$aData = self::get_file( $psFolder, $psFile);
		//update the data
		if (!$aData) $aData=[];
		$aData[] = $poData;
		//put the data back
		self::put_file( $psFolder, $psFile, $aData);
		
		return $aData;
	}
}
?>