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
require_once("$phpinc/appdynamics/common.php");
require_once("$phpinc/appdynamics/core.php");
require_once("$phpinc/appdynamics/account.php");


//#################################################################
//# 
//#################################################################
class cCallsAnalysis{
    public $max, $min, $avg, $sum, $count, $extCalls;
}

//#################################################################
//# 
//#################################################################
class cAppDynTransFlow{
	public $name = null;
	public $children = [];
	
	//*****************************************************************
	public function walk($psApp, $psTier, $psTrans){
		cDebug::enter();
		
		$sMetricPath = cAppDynMetric::transExtNames($psTier, $psTrans);
		$this->walk_metric($psApp, $sMetricPath);
		$this->name = $psTrans;
		
		cDebug::leave();
	}

	//*****************************************************************
	protected function walk_metric($psApp, $psMetricPath){
		cDebug::enter();

		$aCalls = cAppdynCore::GET_Metric_heirarchy($psApp, $psMetricPath, false);
		cDebug::write($psMetricPath);
		
		foreach ($aCalls as $oCall)
			if ($oCall->type == "folder") {
				$sMetricPath = $psMetricPath . "|".$oCall->name."|".cAppDynMetric::EXT_CALLS;
				
				$oChild = new cAppDynTransFlow();
				$this->children[] = $oChild;
				$oChild->name = $oCall->name;
				$oChild->walk_metric($psApp, $sMetricPath);
				
			}
			
		cDebug::leave();
	}
	
	//*****************************************************************
	private function pr_add_children($psApp, $psMetric, $paCalls){
	}
}

//#################################################################
//# CLASSES
//#################################################################

class cAppdynUtil {
	private static $maAppnodes = null;
	
	//*****************************************************************
	public static function get_trans_assoc_array($psApp)
	{	
		$aData = [];
		$aTrans = cAppDyn::GET_Transactions($psApp);
		foreach ($aTrans as $oTrans)
			$aData[$oTrans->name] = $oTrans->id;
			
		return $aData;
	}
	//*****************************************************************
	public static function MergeMetricNodes($paData){
		if (count($paData) == 0)
			return null;
		elseif (count($paData) == 1)
			return array_pop($paData);
		else{
			$aNew = array();
			while (count($paData) >0)
			{
				$aPopped = array_pop($paData);
				if (count($aPopped) > 0){
					while (count($aPopped) > 0)
					{
						$aRow = array_pop($aPopped);
						array_push( $aNew, $aRow);
					}
				}
			}
			return $aNew;
		}
	}


	//*****************************************************************
	public static function Analyse_Metrics($poData)
	{
		$max = 0; 
		$count = 0;
		$items = 0;
		$min = -1;
		$sum=0;
		$avg=0;
		
		foreach( $poData as $oRow)
		{
			$value = $oRow->value;	
		
			$max = max($max, $value, $oRow->max);
			if ($value >0){
				if ($min==-1)
					$min=$value;
				else
					$min = min($min, $value);
			}
				
			if ($value>0){
				$count+=$oRow->count;
				$sum+=$value;
				$items++;
			}
		}
		
		if ($min==-1) $min = 0;
		if ($count>0)
			$avg = $sum/$items;
		
		$oResult = new cCallsAnalysis();
		$oResult->max = $max;
		$oResult->min = $min;
		$oResult->sum = $sum;
		$oResult->avg = round($avg,2);
		$oResult->count= $count;
		
		return $oResult;
	}
	
	//*****************************************************************
	public static function Analyse_heatmap($poData){
		$aDays = [];
		$aHours = [];

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		function pr__add_to_array(&$paArray, $psCol, $psRow, $psValue){
			if (!array_key_exists($psCol, $paArray)) $paArray[$psCol]=[];
			if (!array_key_exists($psRow, $paArray[$psCol])) $paArray[$psCol][$psRow]=0;
			$paArray[$psCol][$psRow] += $psValue;
		};
		
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		function pr__normalise_array(&$paArray){
			$iMax=0;
			foreach ($paArray as $sCol=>$aRows)
				foreach ($aRows as $sRow =>$iValue)
					if ($iValue > $iMax) $iMax = $iValue;
				
			foreach ($paArray as $sCol=>$aRows)
				foreach ($aRows as $sRow =>$iValue)
					$paArray[$sCol][$sRow] = $iValue/$iMax;
		}
		
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		foreach( $poData as $oRow){
			$milli = $oRow->startTimeInMillis;
			$hour = date("H", $milli/1000);
			$min = date("i", $milli/1000); 
			$day = date("w", $milli/1000); 
			$value = $oRow->value;

			pr__add_to_array($aDays,$day,$hour,$value);
			pr__add_to_array($aHours,$hour,$min, $value);
		}
		
		pr__normalise_array($aDays);
		pr__normalise_array($aHours);
		
		return ["days"=>$aDays, "hours"=>$aHours];
	}
	
	//*****************************************************************
	public static function extract_bt_name($psMetric, $psTier){
		$sLeft = cAppdynMetric::tierTransactions($psTier);
		$sOut = substr($psMetric, strlen($sLeft)+1);
		$iPos = strpos($sOut, cAppdynMetric::RESPONSE_TIME);
		$sOut = substr($sOut, 0, $iPos -1);
		return $sOut;
	}
	
	//*****************************************************************
	public static function extract_error_name($psTier, $psMetric){
		$sPattern = "/\|$psTier\|(.*)\|Errors per Minute/";
		if (preg_match($sPattern, $psMetric, $aMatches))
			return $aMatches[1];
		else
			cDebug::error("no match $psMetric with $sPattern");
	}
	
	//*****************************************************************
	public static function extract_RUM_name($psType, $psMetric){
		$sPattern = "/\|$psType\|([^\|]+)\|/";
		if (preg_match($sPattern, $psMetric, $aMatches))
			return $aMatches[1];
		else
			cDebug::error("no match $psMetric with $sPattern");
	}
	
	//*****************************************************************
	public static function extract_bt_id($psMetricName){
		if (preg_match("/\|BT:(\d+)\|/", $psMetricName, $aMatches))
			return $aMatches[1];
		else
			cDebug::error("no match");
	}

	public static function make_time_obj($piTimeinMs){
		$oTime = new cAppDynTimes;
		$oTime->start = $piTimeinMs-5000;
		$oTime->end = $piTimeinMs+5000;
		return $oTime;
	}
	//*****************************************************************
	public static function controller_time_command($poTime){
		return "time-range-type=BETWEEN_TIMES&start-time=".$poTime->start."&end-time=".$poTime->end;
	}
	//*****************************************************************
	public static function controller_short_time_command($poTime,$psKey="timeRange"){
		return "$psKey=Custom_Time_Range.BETWEEN_TIMES.".$poTime->end.".".$poTime->start.".60";
	}
	
	//*****************************************************************
	public static function timestamp_to_date( $piMs){
		$iEpoch = (int) ($piMs/1000);
		return date(cCommon::ENGLISH_DATE_FORMAT, $iEpoch);
	}
	
	//*****************************************************************
	public static function extract_agent_version($psInput){
		if (preg_match("/^[\d\.]+$/",$psInput))
			return $psInput;
		
		if (preg_match("/\s+(v[\d\.]+\s\w+)/",$psInput, $aMatches))
			return $aMatches[1];
		else	
			return "unknown $psInput";
	}
	
	//*****************************************************************
	public static function get_node_id($psAid, $psNodeName){
		$aMachines = cAppdyn::GET_AppNodes($psAid);
		$sNodeID = null;
		
		foreach ($aMachines as $aNodes){
			foreach ($aNodes as $oNode)
				if ($oNode->name == $psNodeName){
					$sNodeID = $oNode->id;
					cDebug::write ("found $sNodeID");
					break;
				}
			if ($sNodeID) break;
		}
		
		return $sNodeID;
	}
	
	//*****************************************************************
	public static function get_node_name($psAid, $psNodeID){
		$aMachines = cAppdyn::GET_AppNodes($psAid);
		$sNodeName = null;
		
		foreach ($aMachines as $aNodes){
			foreach ($aNodes as $oNode)
				if ($oNode->id == $psNodeID){
					$sNodeName = $oNode->name;
					cDebug::write ("found $sNodeName");
					break;
				}
			if ($sNodeName) break;
		}
		
		return $sNodeName;
	}
	
	//*****************************************************************
	public static function get_matching_extcall($poApp, $psExt){
		$aTiers = cAppdyn::GET_Tiers($poApp);
		foreach ($aTiers as $oTier){
			$aTierExt = cAppdyn::GET_tier_ExtCalls_Metric_heirarchy($poApp->name, $oTier->name);
			foreach ($aTierExt as $oExt)
				if ( strpos($oExt->name, $psExt) !== false )
					return $oExt->name;
		}
		return null;
	}
}

?>
