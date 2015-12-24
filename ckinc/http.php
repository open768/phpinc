<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2014 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/
require_once("$phpinc/ckinc/curl.php");
class cHttp{
	const LARGE_URL_DIR = "[cache]/[Largeurls]";
	public $progress_len = 0;
	public $progress_count = 0;
	public $show_progress = false;
	public $HTTPS_CERT_FILENAME = null;
	public $USE_CURL = true;
	
	//*****************************************************************************
	public function getJson($psURL){
		cDebug::extra_debug("getting Json");
		$response = $this->fetch_url($psURL);
		$oResponse = json_decode($response);
		
		return $oResponse;
	}
	
	//*****************************************************************************
	public  function fetch_image($psUrl){
		if ($this->USE_CURL)
			return $this->pr__fetch_curl_image($psUrl);
		else
			cDebug::error("non Curl fetch image not implemented");
	}
	
	//*****************************************************************************
	public  function fetch_url($psUrl){
		if ($this->USE_CURL)
			return $this->pr__fetch_curl_url($psUrl);
		else
			return file_get_contents($psUrl);
	}
	
	public function fetch_to_file($psUrl, $psPath, $pbOverwrite=false, $piTimeOut=60, $pbGzip=false){
		if ($this->USE_CURL)
			return $this->pr__fetch_curl_to_file($psUrl, $psPath, $pbOverwrite, $piTimeOut, $pbGzip);
		else
			cDebug::error("non Curl fetch file not implemented");
	}
		
	//*****************************************************************************
	public function large_url_path($psFilename){
		global $root;
		
		$sDir = "$root/".$this->LARGE_URL_DIR;
		return "$sDir/$psFilename";
	}
	
	//*****************************************************************************
	public function fetch_large_url($psUrl, $psFilename, $pbOverwrite=false)
	{
		global $root;
		
		//check the folder is there
		$sDir = "$root/".$this->LARGE_URL_DIR;
		if (!is_dir( $sDir)){
			cDebug::write("making cache dir $sDir");
			mkdir($sDir, 0700, true);
		}
		
		$sPath = $this->large_url_path($psFilename);
		$this->show_progress = true;
		return $this->fetch_to_file($psUrl, $sPath, $pbOverwrite, 600);
	}
	
	//############################################################################
	//#
	//#  CURL functions
	//#
	//############################################################################
	private function pr__curl_init($psUrl){
		$oCurl = new cCurl($psUrl);	
		$oCurl->setopt( CURLOPT_URL, $psUrl);
		$oCurl->setopt( CURLOPT_FAILONERROR, 1);
		$oCurl->setopt( CURLOPT_RETURNTRANSFER, 1);
		$oCurl->setopt( CURLOPT_FOLLOWLOCATION, true);
		if (cDebug::$EXTRA_DEBUGGING){
			cDebug::extra_debug("enabling CURL_verbosity");
			$oCurl->setopt( CURLOPT_VERBOSE, 1);
		}
		
		//use gzip compression to save bandwidth
		$oCurl->setopt( CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip,deflate'));
		$oCurl->setopt( CURLOPT_ENCODING, ''); 		//decode automatically
		
		//used to show progress
		if ($this->show_progress){
			$parent = $this;
			$oCurl->setopt(
				CURLOPT_PROGRESSFUNCTION, 
				function ($p_Resource, $p_dl_size, $p_dl, $p_ul_size) use (&$parent){$parent->__progress_callback($p_Resource, $p_dl_size, $p_dl, $p_ul_size);}
				);
			$oCurl->setopt( CURLOPT_NOPROGRESS, false); // needed to make progress function work
			$this->show_progress = false;				//cancel show progress, must be set every time
		}

		//https stuff - doesnt work :-( pain in the ass
		if ($this->HTTPS_CERT_FILENAME){
			if (!file_exists($this->HTTPS_CERT_FILENAME))
				cDebug::error("certificate doesnt exist ");
			
			$oCurl->setopt( CURLOPT_SSL_VERIFYPEER, true);
			$oCurl->setopt( CURLOPT_SSL_VERIFYHOST, 2);
			$sCertPath = $root."/".$this->HTTPS_CERT_FILENAME;
			cDebug::extra_debug("set cert to $sCertPath");
			$oCurl->setopt( CURLOPT_CAINFO, $sCertPath);
			$oCurl->setopt( CURLOPT_CAPATH, $sCertPath); //broken
		}
		
		return $oCurl;
	}
	
	
	//*****************************************************************************
	private function pr__fetch_curl_image($psUrl){
		$oCurl = $this->pr__curl_init($psUrl);	
		$oCurl->setopt( CURLOPT_BINARYTRANSFER, 1); 
		$response = $oCurl->exec();
		
		$oImage = imagecreatefromstring($response);
	
		return  $oImage;
	}
	
	//*****************************************************************************
	private function pr__fetch_curl_url($psUrl){
		global $root;
		
		$oCurl = $this->pr__curl_init($psUrl);	
		return  $oCurl->exec();
	}
	
	//*****************************************************************************
	private function pr__fetch_curl_to_file($psUrl, $psPath, $pbOverwrite=false, $piTimeOut=60, $pbGzip=false){
		//check whether the file exists
		if (!$pbOverwrite &&file_exists($psPath)){
			cDebug::write("file exists $psPath");
			return $psPath;
		}
		
		//ok get the file
		cDebug::write("getting url: $psUrl ");
		$this->progress_len = 0;
		$this->progress_count = 0;
		
		$fHandle = fopen($psPath, 'w');
		$oCurl = $this->pr__curl_init($psUrl);	
		$oCurl->setopt( CURLOPT_RETURNTRANSFER, 0);
		$oCurl->setopt( CURLOPT_FILE, $fHandle);
		
		set_time_limit($piTimeOut);
		try{
			$response = $oCurl->exec();
			fclose($fHandle);
		}catch (Exception $e){
			unlink($psPath);
			throw $e;
		}

		return $psPath;
	}
	

	//############################################################################
	//#
	//#  Other  functions
	//#
	//############################################################################
	public function __progress_callback($resource, $dl_size, $dl, $ul_size){
		
		$this->progress_count++;
		if ($this->progress_count < 20) return;
		$this->progress_count  = 0;
		
		$this->progress_len++;
		if ($this->progress_len > 120){ 
			$this->progress_len = 0; 
			echo "<br>";
		}
		echo "*";
		ob_flush();
		flush();
	}
}

?>