<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
//
// ** TBA allow instances of Hash to set their own folders. **
// ** Currently this is set to cache folders **
//
**************************************************************************/

class cGzip{
	const BUFFER = 512;
	
	public static function readObj($psFile){
		$aLines = gzfile($psFile);
		$sSerialised = implode("",$aLines);
		$sSerialised  = stripslashes($sSerialised);
		$oData = unserialize($sSerialised);
		
		return $oData;
	}
	
	//********************************************************************
	public static function encode($pvAnything){
		cDebug::enter();
		
		$sSerial = serialize($pvAnything);
		cDebug::extra_debug("serialised: $sSerial");
		$sZipped = gzencode($sSerial);
		$sEncoded = base64_encode ($sZipped);
		cDebug::extra_debug("encoded: $sEncoded");
		cDebug::leave();
		return $sEncoded;
	}
	
	//********************************************************************
	public static function decode($psEncoded){
		cDebug::enter();
		$sZipped = base64_decode($psEncoded);
		$sSerial = gzdecode($sZipped);
		cDebug::leave();
		return unserialize($sSerial);
	}
	
	//********************************************************************
	public static function writeObj($psFile, $poData){
		$sSerial = serialize($poData);
		$sSerial = addslashes($sSerial);
		$fp = gzopen($psFile, "wb");
		gzwrite($fp, $sSerial);
		gzclose($fp);
	}
	
	//********************************************************************
	public static function isGzipped($psFilename){
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		//cDebug::write($finfo->file($psFilename));
		finfo_close($finfo);
	}
	
	//********************************************************************
	public static function compress_file($psInFile, $psOutFile = null){	
		if ($psOutFile == null)	$psOutFile = "$psInFile.gz";
		if (file_exists($psOutFile)) return;
		
		//write the gzip file chunks at a time
		$fp_in = fopen($psInFile,"r");
		$fp_out = gzopen($psOutFile, "wb");
		while (!feof($fp_in)) 
			gzwrite($fp_out, fread($fp_in, 1024 * self::BUFFER)); 
		fclose($fp_in); 
		gzclose($fp_out);
		
		//delete original with compressed
		unlink ($psInFile);
	}

}

?>
