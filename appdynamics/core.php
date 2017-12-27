<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/appdynamics/common.php");
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/common.php");


//#################################################################
//# 
//#################################################################
class cAppDynTimes{
	public $start;
	public $end;
}

//#################################################################
//# 
//#################################################################
class cAppDynCore{
	public static $CONTROLLER_PREFIX="controller";
	public static $SUFFIX = "&output=JSON";
	const USUAL_METRIC_PREFIX = "/rest/applications/";
	const CONFIG_METRIC_PREFIX = "/rest/configuration";
	const DB_METRIC_PREFIX = "/rest/applications/Database%20Monitoring/metric-data?metric-path=";
	const REST_UI_PREFIX = "/restui/templates/";
	const DATABASE_APPLICATION = "Database Monitoring";
	
	
	public static $URL_PREFIX = self::USUAL_METRIC_PREFIX;
	const DATE_FORMAT="Y-m-d\TG:i:s\Z";

	public static function GET_controller(){
		$oCred = new cAppDynCredentials();
		$oCred->load();
		$sController = ($oCred->use_https?"https":"http")."://$oCred->host";
		
		if (self::$CONTROLLER_PREFIX)
				$sController.= "/".self::$CONTROLLER_PREFIX;
		
		cDebug::extra_debug("controller URL: $sController");
		return $sController;
	}
	
	
	//*****************************************************************
	public static function  GET($psCmd, $pbCacheable = false){
		global $oData;

		cDebug::enter();
		cDebug::write("getting $psCmd");
		if ($pbCacheable && (!cDebug::$IGNORE_CACHE) && cHash::exists($psCmd)){
			$oData = cHash::get($psCmd);
			cDebug::leave();
			return $oData;
		}
		
		//-------------- get session info
		$oCred = new cAppDynCredentials();
		$oCred->load();
		$sCred=$oCred->encode();

		$sAD_REST = self::GET_controller().self::$URL_PREFIX;
		
		
		//----- actually do it
		$url = $sAD_REST.$psCmd.self::$SUFFIX;
		cDebug::extra_debug("Url: $url");
		
		$oHttp = new cHttp();
		$oHttp->USE_CURL = false;
		$oHttp->set_credentials($sCred,$oCred->password);
		$oData = $oHttp->getjson($url);
		
		//----- 
		if ($pbCacheable)	cHash::put($psCmd, $oData,true);

		cDebug::leave();
		return $oData;
	}
	
	
	//*****************************************************************
	//*
	//*****************************************************************
	public static function GET_MetricData($psApp, $psMetricPath, $poTimes , $psRollup=false, $pbCacheable=false, $pbMulti = false)
	{
		cDebug::enter();
		if ($poTimes == null) cDebug::error("times are missing");
		
		$sRangeType = "";
		$sTimeCmd=cAppdynUtil::controller_time_command($poTimes);
		
		$encoded = rawurlencode($psMetricPath);
		$encoded = str_replace(rawurlencode("*"),"*",$encoded);
		$psApp=rawurlencode($psApp);
		
		$url = "$psApp/metric-data?metric-path=$encoded&$sTimeCmd&rollup=$psRollup";
		$oData = self::GET( $url ,$pbCacheable);
		
		$aOutput = $oData;
		if (!$pbMulti && (count($oData) >0))
			$aOutput = $oData[0]->metricValues;
		
		cDebug::leave();
		return $aOutput;		
	}
	
	
	//*****************************************************************
	public static function GET_Metric_heirarchy($psApp, $psMetricPath, $pbCached=true, $poTimes = null)
	{
		cDebug::enter();
		cDebug::extra_debug("get Heirarchy: $psMetricPath");
		$encoded=rawurlencode($psMetricPath);	
		$psApp = rawurlencode($psApp);
		$sCommand = "$psApp/metrics?metric-path=$encoded";
		if ($poTimes !== null){
			$sTimeCmd=cAppdynUtil::controller_time_command($poTimes);
			$sCommand .= "&$sTimeCmd";
		}
		
		$oData = self::GET($sCommand, $pbCached);
		cDebug::leave();
		return $oData;
	}
	

	//*****************************************************************
	public static function GET_TransURL($psAppID, $psTransID){
		$caption = "default";
		$epoch = time();
		
		$duration = cAppDynCommon::get_duration();
		switch($duration){
			case 15:
				$caption = "last_15_mins";
				break;
			case 30:
				$caption = "last_30_mins";
				break;
			case 60:
				$caption = "last_1_hour";
				break;
			case 120:
				$caption = "last_2_hours";
				break;
			case 240:
				$caption = "last_4_hours";
				break;
			case 1440:
				$caption = "last_1_day";
				break;
			case 2880:
				$caption = "last_2_days";
				break;
			case 4320:
				$caption = "last_3_days";
				break;
		}

		$sUrl = self::GET_controller()."/#location=APP_BT_DETAIL&timeRange=$caption.BEFORE_NOW.-1.$epoch.$duration&application=$psAppID&businessTransaction=$psTransID"; 
		return $sUrl;
	}
}

?>
