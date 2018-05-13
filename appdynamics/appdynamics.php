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
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/cached_http.php");
require_once("$phpinc/pubsub/pub-sub.php");
require_once("$phpinc/appdynamics/demo.php");
require_once("$phpinc/appdynamics/common.php");
require_once("$phpinc/appdynamics/auth.php");
require_once("$phpinc/appdynamics/core.php");
require_once("$phpinc/appdynamics/account.php");
require_once("$phpinc/appdynamics/util.php");
require_once("$phpinc/appdynamics/metrics.php");
require_once("$phpinc/appdynamics/controllerui.php");
require_once("$phpinc/appdynamics/restui.php");

require_once("$phpinc/appdynamics/controller.php");
require_once("$phpinc/appdynamics/app.php");
require_once("$phpinc/appdynamics/tier.php");

//#################################################################
//# 
//#################################################################
class cTransExtCalls{
    public $trans1, $trans2, $calls, $times;
}

class cAppdObj{
	public $application;
	public $tier;
	public $business_transaction;
	public $backend;
	public $metric_name;
	public $data = null;
}

class cAppdMetricLeaf{
	public $tier = null;
	public $name = null;
	public $metric = null;
	public $notes = null;
	public $children = [];
	
	public function has_children(){
		return count($this->children);
	}
	
	public function get_matching_names($psFragment, &$aOutput){
		if (strstr($this->name,$psFragment))
			$aOutput[] = $this;
		
		foreach ($this->children as $oChild)
			$oChild->get_matching_names($psFragment, $aOutput);
	}
	
	public function add_child($oChild){
		$this->children[] = $oChild;
	}
}

class cAppDDetails extends cAppdObj{
   public $name, $id, $calls, $times;
   function __construct($psName, $psId, $poCalls, $poTimes) {
		$this->name = $psName;
		$this->id = $psId;
		$this->calls = $poCalls;
		$this->times = $poTimes;
   }
}


cAppDApp::$null_app = new cAppDApp(null,null);
cAppDApp::$db_app = new cAppDApp(cAppDynCore::DATABASE_APPLICATION,cAppDynCore::DATABASE_APPLICATION);




//#################################################################
//# CLASSES
//#################################################################
class cAppDynWebsite{
	const DOWNLOAD_URL = "https://download.appdynamics.com/download/downloadfile/?apm=jvm%2Cdotnet%2Cphp%2Cmachine%2Cwebserver%2Cdb%2Cappd4db%2Canalytics%2Cios%2Candroid%2Ccpp-sdk%2Cpython%2Cnodejs%2Cgolang-sdk%2Cuniversal-agent%2Ciot%2Cnetviz&eum=linux%2Cosx%2Cwindows%2Cgeoserver%2Cgeodata%2Csynthetic&events=linuxwindows&format=json&os=linux%2Cosx%2Cwindows&platform_admin_os=linux%2Cosx%2Cwindows";
	public static function GET_latest_downloads(){
		$oHttp = new cCachedHttp();
		$oHttp->USE_CURL = false;
		$sUrl = self::DOWNLOAD_URL;
		$aData = [];
		while ($sUrl){
			$oData = $oHttp->getCachedJson($sUrl);
			if ($oData->count >0){
				$sUrl = $oData->next;
				foreach ($oData->results as $oDownload)
					$aData[] = $oDownload;
			}else
				$sUrl = null;
		}
		
		uasort($aData,"ad_sort_downloads");
		return $aData;
	}
}

//#################################################################
//# CLASSES
//#################################################################
class cAppDyn{
	const APPDYN_LOGO = 'adlogo.jpg';
	const APPDYN_OVERFLOWING_BT = "_APPDYNAMICS_DEFAULT_TX_";
	const ALL_EVENT_TYPES = "POLICY_OPEN_CRITICAL,POLICY_OPEN_WARNING,POLICY_CLOSE,POLICY_CLOSE_CRITICAL,POLICY_CLOSE_WARNING,POLICY_CONTINUES_CRITICAL";
	const ALL_SEVERITIES = "WARN,ERROR,INFO";
	
	public static $SHOW_PROGRESS = true;
	private static $maAppNodes = null;
	
	private static function pr_flushprint($psChar = cCommon::PROGRESS_CHAR){
		if (self::$SHOW_PROGRESS) cCommon::flushprint($psChar);
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* All
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	
	
	public static function is_demo(){
		$oCred = new cAppDynCredentials();
		$oCred->check();
		return $oCred->is_demo();
	}
	
		
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Databases
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	
	public static function GET_Database_ServerStats($psDB){
		$sMetricPath= cAppDynMetric::databaseServerStats($psDB);
		return  cAppdynCore::GET_Metric_heirarchy(cAppDynCore::DATABASE_APPLICATION, $sMetricPath, false);
	}
		
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* transactions
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
			
	//*****************************************************************
	public static function GET_TransExtTiers($psApp, $psTier, $psTrans){
		$sMetricPath= cAppDynMetric::transExtNames($psTier,$psTrans);
		return cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricPath, false);
	}

	//*****************************************************************
	public static function GET_TransExtCallsPerMin($poTier, $psTrans1, $psOther, $poTimes, $psRollup=false)
	{
		self::pr_flushprint();
		$sMetricPath = cAppDynMetric::transExtCalls($poTier->name,$psTrans1,$psOther);
		$aData = cAppdynCore::GET_MetricData($poTier->app,$sMetricPath, $poTimes, $poTimes,$psRollup);
		return $aData;
	}

	//*****************************************************************
	public static function GET_TransExtResponseTimes($poTier, $psTrans1, $psOther, $poTimes, $psRollup)
	{
		self::pr_flushprint();
		$sMetricPath = cAppDynMetric::transExtResponseTimes($poTier->name,$psTrans1,$psOther);
		$aData = cAppdynCore::GET_MetricData($poTier->app,$sMetricPath, $poTimes,$poTimes,$psRollup);
		return $aData;
	}

	//*****************************************************************
	public static function GET_transExtCalls($poTier, $psTrans, $piMin, $poTimes)
	{
	
		// get the ext calls metric heirarchy for this transactions
		$sMetricPath = "Business Transaction Performance|Business Transactions|$psTier|$psTrans|External Calls";
		self::pr_flushprint();
		$aData =  cAppdynCore::GET_Metric_heirarchy($poTier->app->name, $sMetricPath);
		$aResults = array();
		
		if (count($aData) > 0)
			foreach( $aData as $oRow){
				self::pr_flushprint();
				$sOther = $oRow->name;
				
				$aCalls = self::GET_TransExtCallsPerMin($poTier, $psTrans, $sOther, $piMin, $poTimes,"true");
				$aTimes = self::GET_TransExtResponseTimes($poTier, $psTrans, $sOther, $piMin, $poTimes,"true");
				
				$oRow = new cTransExtCalls();
				$oRow->trans1 = $psTrans;
				$oRow->trans2 = $sOther;
				$oRow->calls = $aCalls;
				$oRow->times = $aTimes;

				array_push($aResults, $oRow);
			}
		 
		return $aResults;
	}
	


	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* tiers
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>	
	

	//*****************************************************************
	public static function GET_tier_ExtCalls_Metric_heirarchy($psApp, $psTier){
		cDebug::enter();
			$metricPath = "Overall Application Performance|$psTier|External Calls";
			$aData = cAppdynCore::GET_Metric_heirarchy($psApp, $metricPath, false);
			uasort ($aData, "ad_sort_by_name");
		cDebug::leave();
		return $aData;
	}

	//*****************************************************************
	public static function GET_Tier_Errors($poTier, $poTimes){
		//get the list of errors seen on the tier
		$sMetricpath = "Errors|$poTier->name";
		$aHeirarchy = cAppdynCore::GET_Metric_heirarchy($poTier->app->name, $sMetricpath, false);
		
		// get the specifics of the error
		$aResults = array(); 
		foreach ($aHeirarchy as $oRow){
			self::pr_flushprint(",");
			
			$sMetric2path= "$sMetricpath|$oRow->name|Errors per Minute";
			$oMetrics = cAppdynCore::GET_MetricData($poApp, $sMetric2path, $poTimes, false);
			
			if ($oMetrics){
					$oStats = cAppdynUtil::Analyse_Metrics($oMetrics);
					if ($oStats->count >0){
						$oDetail = new cAppDDetails($oRow->name, null , null, null);
						$oDetail->calls = $oStats ;
						array_push($aResults, $oDetail);
					}
			}
		}
		
		//return the data
		return $aResults;
	}

	//*****************************************************************
	public static function GET_Tier_ext_details($poTier, $poTimes){
		global $aResults;
		$sApp = $poTier->app->name;
		$sTier = $poTier->name;
		
		cDebug::write("<h3>getting details for $sTier</h3>");
		//first get the metric heirarchy
		self::pr_flushprint(".");
		$oHeirarchy = self::GET_tier_ExtCalls_Metric_heirarchy($sApp, $sTier, false);
			
		//get the transaction IDs TBD
		$trid=1;
		
		//for each row in the browser get external calls per minute
		$aResults = array();
		foreach ($oHeirarchy as $row){
			self::pr_flushprint(".");
			$sOtherTier=$row->name;
			
			cDebug::write("<h4>other tier is $sOtherTier</h4>");
			cDebug::write("<b>Calls per min</b>");
			$oCalls = null;
			$oData = self::GET_TierExtCallsPerMin( $poTier, $sOtherTier, $poTimes, "true");
			if ($oData)	$oCalls = cAppdynUtil::Analyse_Metrics( $oData);
				
			cDebug::write("<b>response times</b>");
			$oTimes = null;
			$oData = self::GET_TierExtResponseTimes($poTier, $sOtherTier, $poTimes, "true");
			if ($oData)	
				$oTimes = cAppdynUtil::Analyse_Metrics( $oData);
			
			cDebug::write("<b>done</b>");
			
			$oDetails = new cAppDDetails($sOtherTier, $trid, $oCalls,  $oTimes);

			array_push($aResults, $oDetails);
		}
		
		//TODO
		return $aResults;
	}

	//*****************************************************************
	public static function GET_TierAppNodes($psApp, $psTier){
		$sMetricpath="Overall Application Performance|$psTier|Individual Nodes";
		$oData = cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false);
		return $oData;
	}

	//*****************************************************************
	// bug in app dynamics - might show zero so need to consolidate External Calls with individual nodes metric
	public static function GET_TierExtCallsPerMin($poTier, $psTier2, $poTimes, $psRollup){
		$sMetricpath= cAppDynMetric::tierExtCallsPerMin($poTier->name, $psTier2);
		return cAppdynCore::GET_MetricData($poTier->app, $sMetricpath, $poTimes, $psRollup);
	}	
	
	//*****************************************************************
	public static function GET_TierExtResponseTimes($poTier, $psTier2, $poTimes, $psRollup){
		$sMetricpath= cAppDynMetric::tierExtResponseTimes($poTier->name, $psTier2);
		return cAppdynCore::GET_MetricData($poTier->app, $sMetricpath, $poTimes, $psRollup);
	}
	
	//*****************************************************************
	public static function GET_TierServiceEndPoints($psApp, $psTier){
		if ( self::is_demo()) return cAppDynDemo::GET_TierServiceEndPoints($psApp,$psTier);
		$sMetricpath= cAppDynMetric::tierServiceEndPoints($psTier);
		$oData = cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false);
		return $oData;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	// Tier infrastructure
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_TierInfraNodes($psApp, $psTier){
		cDebug::enter();
		$sMetricpath=cAppDynMetric::InfrastructureNodes($psTier);
		$aData = cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false);
		uasort($aData, 'ad_sort_by_name');
		cDebug::leave();
		return  $aData;
	}
	
	public static function GET_NodeDisks($psApp, $psTier, $psNode){
		cDebug::enter();
		$sMetricpath=cAppDynMetric::InfrastructureNodeDisks($psTier, $psNode);
		$oData = cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false);
		cDebug::leave();
		return  $oData;
	}
	
	public static function GET_JDBC_Pools($psApp, $psTier, $psNode=null){
		cDebug::enter();
		$sMetricpath=cAppDynMetric::InfrastructureJDBCPools($psTier, $psNode);
		$oData = cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false);
		cDebug::leave();
		return  $oData;
	}
		
}
?>
