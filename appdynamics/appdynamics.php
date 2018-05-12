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

//#################################################################
//# 
//#################################################################
class cAppDApp{
	public static $null_app = null;
	public static $db_app = null;
	public $name, $id;
	function __construct($psAppName, $psAppId) {	
		$this->name = $psAppName;
		$this->id = $psAppId;
	}
   
	//*****************************************************************
	public function GET_Tiers(){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_Tiers($this);
		$sApp = rawurlencode($this->name);
		$aData = cAppdynCore::GET("$sApp/tiers?" );
		if ($aData) uasort($aData,"sort_by_name");
		
		$aOutTiers = [];

		//convert to tier objects and populate the app
		foreach ($aData as $oInTier){
			$oOutTier = new cAppDTier($this, $oInTier->name, $oInTier->id);
			$aOutTiers[] = $oOutTier;
		}
		
		return $aOutTiers;
	}
	
	//*****************************************************************
	public function GET_ExtTiers(){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_AppExtTiers(null);
		cDebug::enter();
		$sMetricPath= cAppDynMetric::appBackends();
		$aMetrics = cAppdynCore::GET_Metric_heirarchy($this->name, $sMetricPath,false); //dont cache
		if ($aMetrics) uasort($aMetrics,"sort_by_name");
		cDebug::leave();
		return $aMetrics;
	}

	//*****************************************************************
	public function GET_InfoPoints($poTimes){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_AppInfoPoints(null);
		return cAppdynCore::GET_Metric_heirarchy($this->name,cAppDynMetric::INFORMATION_POINTS, false, $poTimes);
	}

	//*****************************************************************
	//see events reference at https://docs.appdynamics.com/display/PRO14S/Events+Reference
	public function GET_Events($poTimes, $psEventType = null){
		$sApp = rawurlencode($this->name);
		$sTimeQs = cAppdynUtil::controller_time_command($poTimes);
		if ($psEventType== null) $psEventType = cAppDyn::ALL_EVENT_TYPES;
		$sSeverities = cAppDyn::ALL_SEVERITIES;
		
		$sEventsUrl = cHttp::build_url("$sApp/events", "severities", $sSeverities);
		$sEventsUrl = cHttp::build_url($sEventsUrl, "Output", "JSON");
		$sEventsUrl = cHttp::build_url($sEventsUrl, "event-types", $psEventType);
		$sEventsUrl = cHttp::build_url($sEventsUrl, $sTimeQs);
		return cAppDynCore::GET($sEventsUrl );
	}

	//*****************************************************************
	public function GET_Nodes(){
		$sID = $this->id;
		
		$aResponse = cAppDynCore::GET("$sID/nodes?",true);

		$aOutput = [];
		foreach ($aResponse as $oNode){
			$iMachineID = $oNode->machineId;
			if (!array_key_exists((string)$iMachineID, $aOutput)) $aOutput[(string)$iMachineID] = [];
			$aOutput[(string)$iMachineID][] = $oNode;
		}
		ksort($aOutput );
		
		return $aOutput;
	}

	//*****************************************************************
	public function GET_Transactions(){		
		$sApp = rawurlencode($this->name);
		return cAppDynCore::GET("$sApp/business-transactions?" );
	}

	//*****************************************************************
	public function GET_Backends(){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_Backends(null);
		$sMetricpath= cAppDynMetric::backends();
		return cAppdynCore::GET_Metric_heirarchy($this->name, $sMetricpath, false); //dont cache
	}

	//*****************************************************************
	public function GET_snaphot_info($psTransID, $poTimes){
		/*should use instead
		eg https://waitroseprod.saas.appdynamics.com/controller/restui/snapshot/snapshotListDataWithFilterHandle		{"firstInChain":false,"maxRows":600,"applicationIds":[1424],"businessTransactionIds":[],"applicationComponentIds":[4561],"applicationComponentNodeIds":[],"errorIDs":[],"errorOccured":null,"userExperience":[],"executionTimeInMilis":null,"endToEndLatency":null,"url":null,"sessionId":null,"userPrincipalId":null,"dataCollectorFilter":null,"archived":null,"guids":[],"diagnosticSnapshot":null,"badRequest":null,"deepDivePolicy":[],"rangeSpecifier":{"type":"BEFORE_NOW","durationInMinutes":15}}		
		*/
		
		$sApp = rawurlencode($this->name);
		$sUrl = cHttp::build_url("$sApp/request-snapshots", cAppdynUtil::controller_time_command($poTimes));
		$sUrl = cHttp::build_url($sUrl, "application_name", $sApp);
		//$sUrl = cHttp::build_url($sUrl, "application-component-ids", $psTierID);
		$sUrl = cHttp::build_url($sUrl, "business-transaction-ids", $psTransID);
		$sUrl = cHttp::build_url($sUrl, "output", "JSON");
		return cAppDynCore::GET($sUrl);
	}
}

//#################################################################
//# 
//#################################################################
class cAppDTier{
   public static $null_app = null;
   public static $db_app = null;
   public $name, $id, $app;
   function __construct($poApp, $psTierName, $psTierId) {	
		$this->app = $poApp;
		$this->name = $psTierName; 
		$this->id = $psTierId;
   }
}

cAppDApp::$null_app = new cAppDApp(null,null);
cAppDApp::$db_app = new cAppDApp(cAppDynCore::DATABASE_APPLICATION,cAppDynCore::DATABASE_APPLICATION);



//#################################################################
//# 
//#################################################################
function AD_sort_fn($a,$b)
{
    $v1 = $a->startTimeInMillis;
    $v2 = $b->startTimeInMillis;
    if ($v1==$v2) return 0;
    return ($v1 < $v2) ? -1 : 1;
}

function cDetails_sorter($po1, $po2){
	return strcasecmp ($po1->name, $po2->name);
}

function sort_by_name($po1, $po2){
	return strcasecmp ($po1->name, $po2->name);
}

function sort_machine_agents( $po1, $po2){
	return strcasecmp ($po1->applicationIds[0].".".$po1->hostName, $po2->applicationIds[0].".".$po2->hostName);	
}
function sort_appserver_agents( $po1, $po2){
	return strcasecmp (
		"$po1->applicationName.$po1->applicationComponentName.$po1->hostName", 
		"$po2->applicationName.$po2->applicationComponentName.$po2->hostName"
	);	
}

function sort_downloads($po1, $po2){
	return strcasecmp ($po1->title, $po2->title);	
}

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
		
		uasort($aData,"sort_downloads");
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
	public static function GET_Controller_version(){
		$aConfig = self::GET_configuration();
		foreach ($aConfig as $oItem)
			if ($oItem->name === "schema.version"){
				$sVersion = preg_replace("/^0*/","",$oItem->value);
				$sVersion = preg_replace("/-0+(\d+)/",'.$1',$sVersion);
				$sVersion = preg_replace("/-0+/",'.0',$sVersion);
				return $sVersion;
			}
	}
	
	public static function GET_configuration(){
		$old_prefix = cAppDynCore::$URL_PREFIX;
		cAppDynCore::$URL_PREFIX = cAppDynCore::CONFIG_METRIC_PREFIX ;
		$oData = cAppDynCore::GET("?");
		cAppDynCore::$URL_PREFIX = $old_prefix ;
		return $oData;
	}
	
	public static function is_demo(){
		$oCred = new cAppDynCredentials();
		$oCred->check();
		return $oCred->is_demo();
	}

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* All
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_allBackends(){
		$aServices = [];
		
		$oApps = self::GET_Applications();
		foreach ($oApps as $oApp){
			$aBackends = self::GET_Backends($oApp->name);
			foreach ($aBackends as $oBackend){
				$sBName = $oBackend->name;
				if (!array_key_exists($sBName, $aServices)) $aServices[$sBName] = [];
				$aServices[$sBName][] = new cAppDApp($oApp->name, $oApp->id);
			}
		}
		ksort($aServices);
		return $aServices;
	}
	
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* applications
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>	
	//*****************************************************************
	public static function GET_Applications(){
		if ( self::is_demo()) return cAppDynDemo::GET_Applications();
		
		$aData = cAppDynCore::GET('?');
		if ($aData)	uasort($aData,"sort_by_name");
		
		$aOut = [];
		foreach ($aData as $oItem){
			$oApp = new cAppDApp($oItem->name, $oItem->id);
			$aOut[] = $oApp;
		}
		
		return ($aOut);		
	}
	
		
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Databases
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_Databases(){
		$sMetricPath= cAppDynMetric::databases();
		return  cAppdynCore::GET_Metric_heirarchy(cAppDynCore::DATABASE_APPLICATION, $sMetricPath, false);
	}
	
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
	public static function GET_tier_transaction_names($poTier){
		//find out the transactions in this tier - through metric heirarchy (but doesnt give the trans IDs)
		cDebug::enter();
		$aResults = []; 
		
		try{
			$metricPath = cAppDynMetric::tierTransactions($poTier->name);
			$aTierTransactions = cAppdynCore::GET_Metric_heirarchy($poTier->app->name, $metricPath, false);	
			if (!$aTierTransactions) return null;
			
			//so get the transaction IDs
			$aAppTrans= cAppdynUtil::get_trans_assoc_array($poTier->app);
			
			// and combine the two

			foreach ($aTierTransactions as $oTierTrans){
				if (!array_key_exists($oTierTrans->name, $aAppTrans)) continue;
				
				$sTransID = $aAppTrans[$oTierTrans->name];
				$oDetail = new cAppDDetails($oTierTrans->name, $sTransID, null, null);
				$aResults[] = $oDetail;
			}
			
			uasort($aResults, 'cDetails_sorter');
		}
		catch (Exception $e){
			$aResults = null;
		}
		cDebug::leave();
		return $aResults;
	}
	

	//*****************************************************************
	public static function GET_tier_ExtCalls_Metric_heirarchy($psApp, $psTier){
		cDebug::enter();
			$metricPath = "Overall Application Performance|$psTier|External Calls";
			$aData = cAppdynCore::GET_Metric_heirarchy($psApp, $metricPath, false);
			uasort ($aData, "sort_by_name");
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
		uasort($aData, 'cDetails_sorter');
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
