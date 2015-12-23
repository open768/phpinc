<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2014 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/
class cHttp{
	const LARGE_URL_DIR = "[cache]/[Largeurls]";
	public $progress_len = 0;
	public $progress_count = 0;
	public $show_progress = false;
	public $HTTPS_CERT_FILENAME = null;
	
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
			cDebug::error("non Curl fetch url not implemented");
	}
	
	public function fetch_to_file($psUrl, $psPath, $pbOverwrite=false, $piTimeOut=60, $pbGzip=false){
		if ($this->USE_CURL)
			return $this->pr__fetch_curl_to_file($psUrl, $psPath, $pbOverwrite, $piTimeOut, $pbGzip);
		else
			cDebug::error("non Curl fetch file not implemented");
	}
		
	//############################################################################
	//#
	//#  CURL functions
	//#
	//############################################################################
	private function pr__fetch_curl_image($psUrl){
		$oCurl = curl_init();	
		curl_setopt($oCurl, CURLOPT_URL, $psUrl);
		curl_setopt($oCurl, CURLOPT_FAILONERROR, 1);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_BINARYTRANSFER, 1); 
		curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, true);
		
		if ($this->show_progress)
			$parent = $this;
			curl_setopt(
				$oCurl, 
				CURLOPT_PROGRESSFUNCTION, 
				function ($p_Resource, $p_dl_size, $p_dl, $p_ul_size) use (&$parent){$parent->__progress_callback($p_Resource, $p_dl_size, $p_dl, $p_ul_size);}
				);
			
		if ($this->show_progress){
			curl_setopt($oCurl, CURLOPT_NOPROGRESS, false); // needed to make progress function work
			$this->show_progress = false;
		}
		
		$response = curl_exec($oCurl);
		$iErr = curl_errno($oCurl);
		if ($iErr!=0 ) {
			print curl_error($oCurl)."<p>";
			curl_close($oCurl);
			throw new Exception("ERROR URL was: $psUrl <p>");
		}else
			curl_close($oCurl);
		
		$oImage = imagecreatefromstring($response);
	
		return  $oImage;
	}
	
	//*****************************************************************************
	private function pr__fetch_curl_url($psUrl){
		global $root;
		
		cDebug::extra_debug("curl fetching url: $psUrl");
		$oCurl = curl_init();	
		curl_setopt($oCurl, CURLOPT_URL, $psUrl);
		curl_setopt($oCurl, CURLOPT_FAILONERROR, 1);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, true);
		
		if ($this->HTTPS_CERT_FILENAME){
			if (!file_exists($this->HTTPS_CERT_FILENAME))
				cDebug::error("certificate doesnt exist ");
			
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);
			$sCertPath = $root."/".$this->HTTPS_CERT_FILENAME;
			cDebug::extra_debug("set cert to $sCertPath");
			curl_setopt($oCurl, CURLOPT_CAINFO, $sCertPath);
			curl_setopt($oCurl, CURLOPT_CAPATH, $sCertPath); //broken
		}

		if ($this->show_progress)
			curl_setopt($oCurl, CURLOPT_PROGRESSFUNCTION, '__progress_callback');
		
		//use gzip compression to save bandwidth
		curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip,deflate'));
		curl_setopt($oCurl, CURLOPT_ENCODING, ''); 		//decode automatically
		
		if ($this->show_progress){
			curl_setopt($oCurl, CURLOPT_NOPROGRESS, false); // needed to make progress function work
			$this->show_progress = false;
		}
		
		if (cDebug::$EXTRA_DEBUGGING){
			cDebug::write("enabling CURL_verbosity");
			curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
		}
		
		$response = curl_exec($oCurl);
		$iErr = curl_errno($oCurl);
		if ($iErr!=0 ) {
			print curl_error($oCurl)."<p>";
			curl_close($oCurl);
			throw new Exception("ERROR URL was: $psUrl <p>");
		}else{
			cDebug::extra_debug("no error reported by Curl");
			curl_close($oCurl);
		}
		return  $response;
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
		$oCurl = curl_init();	
		curl_setopt($oCurl, CURLOPT_URL, $psUrl);
		curl_setopt($oCurl, CURLOPT_FAILONERROR, 1);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 0);
		curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, true);
		if ($this->show_progress)
			curl_setopt($oCurl, CURLOPT_PROGRESSFUNCTION, '__progress_callback');
			
		//use gzip compression to save bandwidth
		curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip,deflate'));
		if (!$pbGzip)
			curl_setopt($oCurl, CURLOPT_ENCODING, ''); 		//decode automatically
		
		if ($this->show_progress){
			curl_setopt($oCurl, CURLOPT_NOPROGRESS, false); // needed to make progress function work
			$this->show_progress = false;
		}

		curl_setopt($oCurl, CURLOPT_FILE, $fHandle);
		$iErr = 0;
		set_time_limit($piTimeOut);
		$response = curl_exec($oCurl);
		$iErr = curl_errno($oCurl);
		if ($iErr!=0 ) 	print curl_error($oCurl)."<p>";
		curl_close($oCurl);
		fclose($fHandle);
		cDebug::write("ok got $psUrl ");

		if ($iErr != 0){
			unlink($psPath);
			throw new Exception("ERROR URL was: $psUrl <p>");
		}

		return $psPath;
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
	
	//****************************************************************
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