<?php
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//% OBJSTORE - simplistic store objects without a database!
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

require_once("$phpinc/ckinc/gz.php");
require_once("$phpinc/ckinc/common.php");

cObjStore::$rootFolder= "$root/[objdata]";

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
	//TODO migrate to using hash.php
	public static function get_file( $psFolder, $psFile){
		$aData = null;
		
		$num_args = func_num_args();
		if ($num_args != 2) cDebug::error("get_file: incorrect number of arguments - expected 2 got $num_args ");
		
		$sFolder = self::pr_get_folder_path( $psFolder);
		//cDebug::extra_debug("looking for file:$psFile in folder:$sFolder");
		if (!is_dir($sFolder)){
			cDebug::extra_debug("no objstore data at all in folder: $psFolder");
			return $aData;
		}
		
		$sFile = "$sFolder/$psFile";
		//cDebug::write("File: $sFile");
		if (file_exists($sFile))
			$aData = cGzip::readObj($sFile);
		
		return $aData;
	}
	
	//********************************************************************
	public static function put_file( $psFolder, $psFile, $poData){
			
		$num_args = func_num_args();
		if ($num_args != 3) cDebug::error("put_file: incorrect number of arguments - expected 3 got $num_args ");
		
		//check that there is something to write
		if ($poData == null) cDebug::error("put_file: no data to write");
		
		//check that the folder exists
		$sFolder = self::pr_get_folder_path( $psFolder);
		if (!file_exists($sFolder)){
			cDebug::extra_debug("creating folder: $sFolder");
			mkdir($sFolder, 0700, true);
		}
		cDebug::extra_debug("folder exists: $sFolder");

		
		//write out the file
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