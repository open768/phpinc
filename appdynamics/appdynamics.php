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
require_once("$phpinc/pubsub/pub-sub.php");
require_once("$phpinc/appdynamics/common.php");
require_once("$phpinc/appdynamics/auth.php");
require_once("$phpinc/appdynamics/core.php");
require_once("$phpinc/appdynamics/account.php");
require_once("$phpinc/appdynamics/util.php");
require_once("$phpinc/appdynamics/metrics.php");
require_once("$phpinc/appdynamics/controllerui.php");

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

class cDetails extends cAppdObj{
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


//#################################################################
//# CLASSES
//#################################################################
class cAppDyn{
	const APPDYN_LOGO = 'adlogo.jpg';
	public static $SHOW_PROGRESS = true;
	private static $maAppNodes = null;
	
	private static function pr_flushprint($psChar = cCommon::PROGRESS_CHAR){
		if (self::$SHOW_PROGRESS) cCommon::flushprint($psChar);
	}
	
	public static function GET_configuration(){
		$old_prefix = cAppDynCore::$URL_PREFIX;
		cAppDynCore::$URL_PREFIX = cAppDynCore::CONFIG_METRIC_PREFIX ;
		$oData = cAppDynCore::GET("?");
		cAppDynCore::$URL_PREFIX = $old_prefix ;
		return $oData;
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
				$aServices[$sBName][] = (object)["name"=>$oApp->name, "id"=>$oApp->id];
			}
		}
		ksort($aServices);
		return $aServices;
	}
	
	//*****************************************************************
	public static function GET_wildcardData( $psApp, $psMetric, $poTimes){
		return cAppdynCore::GET_MetricData($psApp, $psMetric, $poTimes,"true",false,true);
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* applications
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_Applications(){
		return cAppDynCore::GET('?');
	}

	//*****************************************************************
	public static function GET_AppNodes($piAppID){
		if (self::$maAppNodes != null) return self::$maAppNodes;
		
		$aResponse = cAppDynCore::GET("$piAppID/nodes?");
		
		$aOutput = [];
		foreach ($aResponse as $oNode){
			$iMachineID = $oNode->machineId;
			if (!array_key_exists((string)$iMachineID, $aOutput)) $aOutput[(string)$iMachineID] = [];
			$aOutput[(string)$iMachineID][] = $oNode;
		}
		ksort($aOutput );
		self::$maAppNodes = $aOutput;
		
		return $aOutput;
	}
	
	//*****************************************************************
	public static function GET_AppInfoPoints($psApp, $poTimes){
		return cAppdynCore::GET_Metric_heirarchy($psApp,cAppDynMetric::INFORMATION_POINTS, false, $poTimes);
	}
		
	//*****************************************************************
	public static function GET_AppExtTiers($psApp){
		$sMetricPath= cAppDynMetric::appBackends();
		return cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricPath,false); //dont cache
	}

	//*****************************************************************
	//see events reference at https://docs.appdynamics.com/display/PRO14S/Events+Reference
	public static function GET_AppEvents($psApp, $poTimes, $psEventType = null){
		$psApp = rawurlencode($psApp);
		$sTimeQs = cAppdynUtil::controller_time_command($poTimes);
		if ($psEventType== null) $psEventType = "POLICY_OPEN_CRITICAL,POLICY_OPEN_WARNING,POLICY_CLOSE,POLICY_CLOSE_CRITICAL,POLICY_CLOSE_WARNING,POLICY_CONTINUES_CRITICAL";
		$sSeverities = "WARN,ERROR,INFO";
		$sEventsUrl = cHttp::build_url("$psApp/events", "severities", $sSeverities);
		$sEventsUrl = cHttp::build_url($sEventsUrl, "Output", "JSON");
		$sEventsUrl = cHttp::build_url($sEventsUrl, "event-types", $psEventType);
		$sEventsUrl = cHttp::build_url($sEventsUrl, $sTimeQs);
		return cAppDynCore::GET($sEventsUrl );
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* RUM
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_RUM_pages($psApp){
		$sMetricPath= cAppDynMetric::webrumPages();
		return cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricPath, false);
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
	//* Nodes  (warning this uses an undocumented API / doesnt work unless from the controller)
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_Node_details($piAppID, $piNodeID){
		cAppDynCore::$URL_PREFIX=cAppDynCore::REST_UI_PREFIX ;
		cAppDynCore::$SUFFIX = "";
		$sURL = "dashboardNodeViewData/$piAppID/$piNodeID";
		return  cAppdynCore::GET($sURL);
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* transactions
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
		
	//*****************************************************************
	public static function GET_Transactions($psApp){
		
		$psApp = rawurlencode($psApp);
		return cAppDynCore::GET("$psApp/business-transactions?" );
	}
	
	//*****************************************************************
	public static function GET_TransExtTiers($psApp, $psTier, $psTrans){
		$sMetricPath= cAppDynMetric::transExtNames($psTier,$psTrans);
		return cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricPath, false);
	}

	//*****************************************************************
	public static function GET_TransExtCallsPerMin($psApp, $psTier, $psTrans1, $psOther, $poTimes, $psRollup=false)
	{
		self::pr_flushprint();
		$sMetricPath = cAppDynMetric::transExtCalls($psTier,$psTrans1,$psOther);
		$aData = cAppdynCore::GET_MetricData($psApp,$sMetricPath, $poTimes, $poTimes,$psRollup);
		return $aData;
	}

	//*****************************************************************
	public static function GET_TransExtResponseTimes($psApp, $psTier, $psTrans1, $psOther, $poTimes, $psRollup)
	{
		self::pr_flushprint();
		$sMetricPath = cAppDynMetric::transExtResponseTimes($psTier,$psTrans1,$psOther);
		$aData = cAppdynCore::GET_MetricData($psApp,$sMetricPath, $poTimes,$poTimes,$psRollup);
		return $aData;
	}

	//*****************************************************************
	public static function GET_transExtCalls($psApp, $psTier, $psTrans, $piMin, $poTimes)
	{
	
		// get the ext calls metric heirarchy for this transactions
		$sMetricPath = "Business Transaction Performance|Business Transactions|$psTier|$psTrans|External Calls";
		self::pr_flushprint();
		$aData =  cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricPath);
		$aResults = array();
		
		if (count($aData) > 0)
			foreach( $aData as $oRow){
				self::pr_flushprint();
				$sOther = $oRow->name;
				
				$aCalls = self::GET_TransExtCallsPerMin($psApp, $psTier, $psTrans, $sOther, $piMin, $poTimes,"true");
				$aTimes = self::GET_TransExtResponseTimes($psApp, $psTier, $psTrans, $sOther, $piMin, $poTimes,"true");
				
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
	
	public static function GET_Tiers($psApp){
		$psApp = rawurlencode($psApp);
		return cAppdynCore::GET("$psApp/tiers?" );
	}
	
	//*****************************************************************
	public static function GET_tier_transaction_names($psApp, $psTier){
		//find out the transactions in this tier - through metric heirarchy (but doesnt give the trans IDs)
		cDebug::enter();
		$metricPath = cAppDynMetric::tierTransactions($psTier);
		$aTierTransactions = cAppdynCore::GET_Metric_heirarchy($psApp, $metricPath, false);	
		if (!$aTierTransactions) return null;
		
		//so get the transaction IDs
		$aAppTrans= cAppdynUtil::get_trans_assoc_array($psApp);
		
		// and combine the two
		$aResults = []; 

		foreach ($aTierTransactions as $oTierTrans){
			if (!array_key_exists($oTierTrans->name, $aAppTrans)) continue;
			
			$sTransID = $aAppTrans[$oTierTrans->name];
			$oDetail = new cDetails($oTierTrans->name, $sTransID, null, null);
			$aResults[] = $oDetail;
		}
		
		uasort($aResults, 'cDetails_sorter');
		cDebug::leave();
		return $aResults;
	}
	

	//*****************************************************************
	public static function GET_tier_ExtCalls_Metric_heirarchy($psApp, $psTier){
		$metricPath = "Overall Application Performance|$psTier|External Calls";
		return cAppdynCore::GET_Metric_heirarchy($psApp, $metricPath, false);
	}

	//*****************************************************************
	public static function GET_Tier_Errors($psApp, $psTier, $poTimes){
		//get the list of errors seen on the tier
		$sMetricpath = "Errors|$psTier";
		$aHeirarchy = cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false);
		
		// get the specifics of the error
		$aResults = array(); 
		foreach ($aHeirarchy as $oRow){
			self::pr_flushprint(",");
			
			$sMetric2path= "$sMetricpath|$oRow->name|Errors per Minute";
			$oMetrics = cAppdynCore::GET_MetricData($psApp, $sMetric2path, $poTimes, false);
			
			if ($oMetrics){
					$oStats = cAppdynUtil::Analyse_Metrics($oMetrics);
					if ($oStats->count >0){
						$oDetail = new cDetails($oRow->name, null , null, null);
						$oDetail->calls = $oStats ;
						array_push($aResults, $oDetail);
					}
			}
		}
		
		//return the data
		return $aResults;
	}

	//*****************************************************************
	public static function GET_Tier_ext_details($psApp, $psTier, $poTimes){
		global $aResults;
		
		cDebug::write("<h3>getting details for $psTier</h3>");
		//first get the metric heirarchy
		self::pr_flushprint(".");
		$oHeirarchy = self::GET_tier_ExtCalls_Metric_heirarchy($psApp, $psTier, false);
			
		//get the transaction IDs TBD
		$tid=1;
		
		//for each row in the browser get external calls per minute
		$aResults = array();
		foreach ($oHeirarchy as $row){
			self::pr_flushprint(".");
			$sOtherTier=$row->name;
			
			cDebug::write("<h4>other tier is $sOtherTier</h4>");
			cDebug::write("<b>Calls per min</b>");
			$oCalls = null;
			$oData = self::GET_TierExtCallsPerMin( $psApp, $psTier, $sOtherTier, $poTimes, "true");
			if ($oData)	$oCalls = cAppdynUtil::Analyse_Metrics( $oData);
				
			cDebug::write("<b>response times</b>");
			$oTimes = null;
			$oData = self::GET_TierExtResponseTimes($psApp, $psTier, $sOtherTier, $poTimes, "true");
			if ($oData)	
				$oTimes = cAppdynUtil::Analyse_Metrics( $oData);
			
			cDebug::write("<b>done</b>");
			
			$oDetails = new cDetails($sOtherTier, $tid, $oCalls,  $oTimes);

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
	public static function GET_TierExtCallsPerMin($psApp, $psTier1, $psTier2, $poTimes, $psRollup){
		$sMetricpath= cAppDynMetric::tierExtCallsPerMin($psTier1, $psTier2);
		return cAppdynCore::GET_MetricData($psApp, $sMetricpath, $poTimes, $psRollup);
	}	
	
	//*****************************************************************
	public static function GET_TierExtResponseTimes($psApp, $psTier1, $psTier2, $poTimes, $psRollup){
		$sMetricpath= cAppDynMetric::tierExtResponseTimes($psTier1, $psTier2);
		return cAppdynCore::GET_MetricData($psApp, $sMetricpath, $poTimes, $psRollup);
	}
	
	//*****************************************************************
	public static function GET_TierServiceEndPoints($psApp, $psTier){
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
		$oData = cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false);
		cDebug::leave();
		return  $oData;
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
	
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Backends
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_Backends($psApp){
		$sMetricpath= cAppDynMetric::backends();
		return cAppdynCore::GET_Metric_heirarchy($psApp, $sMetricpath, false); //dont cache
	}
	
	//*****************************************************************
	public static function GET_BackendCallerTiers($psApp, $psBackend){
		$aResult = [];
		
		$oTiers = self::GET_Tiers($psApp);
		foreach ($oTiers as $oTier){
			$sTier = $oTier->name;
			$oExtCalls = self::GET_tier_ExtCalls_Metric_heirarchy($psApp, $sTier, false);
			if (!$oExtCalls) continue;
			
			foreach ($oExtCalls as $oExtCall){
				self::pr_flushprint();
				$sName = $oExtCall->name;
				if (strstr($sName, "to $psBackend")){
					cDebug::write("found a match tier:$sTier name:$sName");
					$oObj = new cAppdObj();
					$oObj->tier = $sTier;
					$oObj->backend = $psBackend;
					$oObj->name = $sName;
					
					$aResult[] = $oObj;
				}
			}
		}
		
		return $aResult;
	}
	
	
	//*****************************************************************
	public static function GET_trans_backend_tree($psApp,  $psFolderMetric = null, $piDepth=0, &$poLeaf = null){
		cDebug::enter();
		$aData = [];
		
		if (!$psFolderMetric){
			$aTiers = self::GET_Tiers($psApp);
			$iCount = count($aTiers);

			foreach ($aTiers as $oTier){
				self::pr_flushprint(" ");
				self::pr_flushprint($iCount--);
				$sTier = $oTier->name;
				$aBTs = self::GET_tier_transaction_names($psApp, $sTier);
				if (!$aBTs) continue;
				
				$oTierObj = new cAppdObj();
				$aData[] = $oTierObj;
				$oTierObj->tier = $sTier;
				$oTierObj->data = [];
				
				foreach ($aBTs as $oBT){
					self::pr_flushprint(".");
					$sBT = $oBT->name;
					cDebug::extra_debug("BT is ($sBT)");
					$sFolderMetric = cAppDynMetric::transMetric($sTier,$sBT);
					
					$oLeaf = new cAppdMetricLeaf();
					$oTierObj->data[] = $oLeaf;
					
					$oLeaf->name = $sBT;
					$oLeaf->metric = $sFolderMetric;
					self::GET_trans_backend_tree($psApp,$sFolderMetric,1, $oLeaf);
				}
			}
		}else{
			$sSearchMetric = $psFolderMetric . "|External Calls";
			$aExtCalls = cAppdynCore::GET_Metric_heirarchy($psApp, $sSearchMetric);
			if ($aExtCalls){
				foreach ($aExtCalls as $oExtCall){
					//----------------------------------------------------------------
					self::pr_flushprint(", ");
					$sExtCallName = $oExtCall->name;
					$sExtMetric = $sSearchMetric."|$sExtCallName";
					cDebug::extra_debug("metric is ($sExtMetric)");
					
					$oLeaf = new cAppdMetricLeaf();
					$poLeaf->add_child($oLeaf);
					$oLeaf->metric = $sExtMetric;
					$oLeaf->name = $sExtCallName;
					
					//----------------------------------------------------------------
					$aItems = cAppdynCore::GET_Metric_heirarchy($psApp, $sExtMetric);
					
					foreach ($aItems as $oItem){
						self::pr_flushprint(".");
						if ($oItem->type !== "folder") continue;
						$sItemName = $oItem->name;
						$sNewMetric = $sExtMetric."|$sItemName";
						self::GET_trans_backend_tree($psApp, $sNewMetric,$piDepth+1, $oLeaf);
					}
				}
			}
			
		}
		cDebug::leave();
		return $aData;
	}
	
	
	//*****************************************************************
	public static function GET_BackendCallerTransactions($psApp, $psBackend){
		$aMatches = [];
		$aData = self::GET_trans_backend_tree($psApp);

		foreach ($aData as $oTier){
			$sTier = $oTier->tier;
			foreach ($oTier->data as $oLeaf)
				$oLeaf->get_matching_names($psBackend, $aMatches);
		}
		
		return $aMatches;
	}

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	// Snapshots
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_snaphot_info($psApp, $psTransID, $poTimes){
		$psApp = rawurlencode($psApp);
		$sUrl = cHttp::build_url("$psApp/request-snapshots", cAppdynUtil::controller_time_command($poTimes));
		$sUrl = cHttp::build_url($sUrl, "application_name", $psApp);
		//$sUrl = cHttp::build_url($sUrl, "application-component-ids", $psTierID);
		$sUrl = cHttp::build_url($sUrl, "business-transaction-ids", $psTransID);
		$sUrl = cHttp::build_url($sUrl, "output", "JSON");
		return cAppDynCore::GET($sUrl);
	}
	
}
?>