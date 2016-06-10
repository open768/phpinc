<?php
require_once("$phpinc/ckinc/debug.php");

class cHeader{
	//*******************************************************************
	public static function redirect_if_referred(){
		if (isset($_SERVER['HTTP_REFERER'])){
			$sUrl = $_SERVER['HTTP_REFERER'];
			$aRef = parse_url($sUrl);
			
			$sPath = basename($aRef["path"]);
			$sThis = pathinfo(basename($_SERVER['PHP_SELF']))["filename"];
			
			if ($sPath === "$sThis.html"){
				$sRedir = "$sThis.php?". $aRef["query"];
				$_SERVER['HTTP_REFERER'] = null;
				self::redirect($sRedir);
				exit;
			}
		}
	}
	
	//*******************************************************************
	public static function redirect($psUrl){
		if (cDebug::is_debugging()){
			cDebug::write("Redirect stopped: $psUrl");
			exit;
		}
		if (headers_sent()){
			?><script language="javascript">
				document.location = "<?=$psUrl?>";
			</script><?php
		}else	
			header("Location: $psUrl");
	}
	
	//*******************************************************************
	public static function is_localhost(){
		$aList = array(
			'127.0.0.1',
			'::1'
		);

		$sServer = $_SERVER['REMOTE_ADDR'];
		$bLocal = in_array($sServer, $aList);
		cDebug::write("Server: '$sServer', local: $bLocal");
		return $bLocal;
	}
	
	//*******************************************************************
	public static function get($psKey){
		$sValue = null;
		if (isset( $_GET[$psKey]))
			$sValue = $_GET[$psKey];
		else if (isset( $_POST[$psKey]))
			$sValue = $_POST[$psKey];
		else
			cDebug::extra_debug("key:$psKey not found in GET or POST");
		
		while (strstr($sValue,"%")){
			$sDecoded = urldecode($sValue);	//reverse proxy double encodes strings
			if ($sDecoded == $sValue) 
				break;
			$sValue = $sDecoded;
		}
		return $sValue;
	}
	
	//*******************************************************************
	//from http://snipplr.com/view.php?codeview&id=2734
	public static function get_server()
	{
		$sServerName = ( empty($_SERVER["HTTP_X_FORWARDED_SERVER"])?$_SERVER['SERVER_NAME']:$_SERVER["HTTP_X_FORWARDED_SERVER"]);
		$sPort = ( empty($_SERVER["HTTP_X_FORWARDED_PORT"])?$_SERVER['SERVER_PORT']:$_SERVER["HTTP_X_FORWARDED_PORT"]);
	
		if (!empty ($_SERVER["HTTP_X_FORWARDED_PROTO"] ))
			$sProto = $_SERVER["HTTP_X_FORWARDED_PROTO"];
		else{
			$aSplit = explode("/", $_SERVER['SERVER_PROTOCOL']);
			$sProto = $aSplit[0];
		}
		$sProto = strtolower($sProto);
		
		if ($sProto == "https") {
			$sPort = ($sPort=="443"?"":":$sPort");
		}else{
			$sPort = ($sPort=="80"?"":":$sPort");
		}


		return $sProto . "://$sServerName$sPort";
	}
	
	//*******************************************************************
	public static function get_page_url(){
		$sServer = self::get_server();
		
		return $sServer.$_SERVER["SCRIPT_NAME"];
	}
	

	//*******************************************************************
	public static function set_download_filename($psFilename){
		if (cDebug::is_debugging()){
			cDebug::write("download file would have been set to $psFilename");
			return;
		}
		
		if (headers_sent()) cDebug::error("Cant set filename, headers sent");
		header("Content-type: text/csv");
		header("Content-Disposition: attachment;filename=$psFilename");
		header("Pragma: no-cache");
		header("Expires: 0");
	}
}
?>