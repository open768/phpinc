<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/
require_once("$phpinc/appdynamics/core.php");

class cAppdynRestUITime{
	public $type="BETWEEN_TIMES";
	public $durationInMinutes = 60;
	public $endTime = -1;
	public $startTime = -1;
	public $timeRange=null;
	public $timeRangeAdjusted=false;
}

class cAppdynRestUIRequest{
	public $applicationIds = [];
	public $guids = [];
	public $rangeSpecifier = null;
	public $needExitCalls = true;
	
	function __construct(){
		$this->rangeSpecifier = new cAppdynRestUITime;
	}
}

class cAppDynRestUI{
	public static $oTimes = null;
	
	public static function GET_database_agents(){
		$sURL = "agent/setting/getDBAgents";
		return  cAppdynCore::GET_restUI($sURL);
	}
	public static function GET_machine_agents(){
		$aAgents = cAppdynCore::GET_restUI("agent/setting/allMachineAgents");
		uasort($aAgents,"sort_machine_agents");
		return  $aAgents;
	}
	public static function GET_appserver_agents(){
		$aAgents = cAppdynCore::GET_restUI("agent/setting/getAppServerAgents");
		uasort($aAgents,"sort_appserver_agents");
		return  $aAgents;
	}

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Nodes  (warning this uses an undocumented API)
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_Node_details($piAppID, $piNodeID){
		$sURL = "dashboardNodeViewData/$piAppID/$piNodeID";
		return  cAppdynCore::GET_restUI($sURL);
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Snapshots (warning this uses an undocumented API)
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_snapshot_segments($psGUID, $piSnapTime){
		cDebug::enter();
			$oTime = cAppdynUtil::make_time_obj($piSnapTime);
			$sTimeUrl = cAppdynUtil::controller_short_time_command( $oTime);
			$sURL = "snapshot/getRequestSegmentData?requestGUID=$psGUID&$sTimeUrl";
			$aResult = cAppdynCore::GET_restUI($sURL);
		cDebug::leave();
		return  $aResult;
	}
	
	//************************************************************************************
	public static function GET_snapshot_problems($poApp,$psGUID, $piSnapTime){
		cDebug::enter();
			$oTime = cAppdynUtil::make_time_obj($piSnapTime);
			$sTimeUrl = cAppdynUtil::controller_short_time_command( $oTime, "time-range");
			$sURL = "snapshot/potentialProblems?request-guid=$psGUID&applicationId=$poApp->id&$sTimeUrl&max-problems=50&max-rsds=30&exe-time-threshold=5";
			$aResult = cAppdynCore::GET_restUI($sURL);
		cDebug::leave();
		return  $aResult;
	}
	
	//************************************************************************************
	public static function GET_snapshot_flow($poSnapShot){
		cDebug::enter();
			$oTime = cAppdynUtil::make_time_obj($poSnapShot->serverStartTime);
			$sAid = $poSnapShot->applicationId;
			$sBtID = $poSnapShot->businessTransactionId;
			$sGUID = $poSnapShot->requestGUID;
			$sTimeUrl = cAppdynUtil::controller_short_time_command( $oTime);
			$sURL = "snapshotFlowmap/distributedSnapshotFlow?applicationId=$sAid&businessTransactionId=$sBtID&requestGUID=$sGUID&eventType=&$sTimeUrl&mapId=-1";
			$oResult = cAppdynCore::GET_restUI($sURL);
		cDebug::leave();
		
		return $oResult;
	}

	//************************************************************************************
	public static function GET_snapshot_expensive_methods($psGUID, $piSnapTime){
		cDebug::enter();
			$oTime = cAppdynUtil::make_time_obj($piSnapTime);
			$sTimeUrl = cAppdynUtil::controller_short_time_command( $oTime);
			$sURL = "snapshot/getMostExpensiveMethods?limit=30&max-rsds=30&$sTimeUrl&mapId=-1";
			$oResult = cAppdynCore::GET_restUI_with_payload($sURL,$psGUID);
		cDebug::leave();
		
		return $oResult;
	}
	
	//************************************************************************************
	/*
	call graph
	https://waitroseprod.saas.appdynamics.com/controller/restui/snapshot/getCallGraphRoot?rsdId=5727317326&timeRange=Custom_Time_Range.BETWEEN_TIMES.1522445638055.1522442038055.60
	*/

}