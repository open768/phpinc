<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2016 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//#################################################################
//# CLASSES
//#################################################################
class cAppDynControllerUI{
	public static function home(){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=AD_HOME";
	}
	public static function databases(){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=DB_MONITORING_SERVER_LIST";
	}
	
	public static function application($piAppID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_DASHBOARD&application=$piAppID";
	}

	public static function businessTransactions($piAppID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_BT_LIST&application=$piAppID";
	}
	
	public static function tier($piAppID, $piTierID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_COMPONENT_MANAGER&application=$piAppID&component=$piTierID&dashboardMode=force";
	}
	
	public static function transaction($piAppID, $piTransID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_BT_DETAIL&application=$piAppID&businessTransaction=$piTransID&dashboardMode=force";
	}

	public static function remoteServices($piAppID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_BACKEND_LIST&application=$piAppID";
	}

	public static function nodes($piAppID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_INFRASTRUCTURE&application=$piAppID&appServerListMode=grid";
	}

	public static function nodeDashboard($piAppID, $piNodeID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_NODE_MANAGER&application=$piAppID&node=$piNodeID&dashboardMode=force";
	}
	
	public static function nodeAgent($piAppID, $piNode){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."#/location=APP_NODE_AGENTS&application=$piAppID&bypassAssociatedLocationsCheck=true&tab=10&node=$piNode&memoryViewMode=0";
	}
	
	public static function machineDetails($piMachineID){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=INFRASTRUCTURE_MACHINE_DETAIL&machine=$piMachineID";
	}

	public static function webrum($piAppID){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=APP_EUM_WEB_MAIN_DASHBOARD&application=$piAppID";
	}
	
	public static function licenses(){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=SETTINGS_LICENSE";
	}
	
	public static function events($piAppID){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=APP_EVENTSTREAM_LIST&application=$piAppID";
	}

	public static function event_detail($piEventID){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=APP_EVENT_VIEWER_MODAL&eventSummary=$piEventID";
	}

	public static function snapshot($piAppID, $piTransID, $psGuid, $poTimes){
		$sBaseUrl = cAppDynCore::GET_controller();	
		$sTimeRange = self::time_command($poTimes);
		$sTimeRSD = self::time_command($poTimes, "rsdTime");
		return $sBaseUrl."/#/location=APP_SNAPSHOT_VIEWER&$sTimeRange&application=$piAppID&bypassAssociatedLocationsCheck=true&tab=1&businessTransaction=$piTransID&requestGUID=$psGuid&$sTimeRSD&dashboardMode=force";
	}
	
	public static function transaction_snapshots($piAppID, $piTransID, $poTimes){
		$sBaseUrl = cAppDynCore::GET_controller();	
		$sTime = self::time_command($poTimes);
		return $sBaseUrl."/#/location=APP_BT_ALL_SNAPSHOT_LIST&application=$piAppID&bypassAssociatedLocationsCheck=true&tab=1&businessTransaction=$piTransID&$sTime";
	}
	
	public static function all_node_snapshots($piAppId, $piNode, $piTransID, $poTimes){
		//http://appdynamics.bluee.net:8090/controller/#/location=APP_NODE_ALL_SNAPSHOT_LIST&timeRange=Custom_Time_Range.BETWEEN_TIMES.1470909665000.1470906065000.0&application=16&node=1902&gridFilters=%257B%2522firstInChain%2522%253Afalse%252C%2522maxRows%2522%253A600%252C%2522applicationIds%2522%253A%255B16%255D%252C%2522businessTransactionIds%2522%253A%255B3640%255D%252C%2522applicationComponentIds%2522%253A%255B%255D%252C%2522applicationComponentNodeIds%2522%253A%255B%255D%252C%2522errorIDs%2522%253A%255B%255D%252C%2522errorOccured%2522%253Anull%252C%2522userExperience%2522%253A%255B%255D%252C%2522executionTimeInMilis%2522%253Anull%252C%2522endToEndLatency%2522%253Anull%252C%2522url%2522%253Anull%252C%2522sessionId%2522%253Anull%252C%2522userPrincipalId%2522%253Anull%252C%2522dataCollectorFilter%2522%253Anull%252C%2522archived%2522%253Anull%252C%2522guids%2522%253A%255B%255D%252C%2522diagnosticSnapshot%2522%253Anull%252C%2522badRequest%2522%253Anull%252C%2522deepDivePolicy%2522%253A%255B%255D%252C%2522rangeSpecifier%2522%253A%257B%2522type%2522%253A%2522BETWEEN_TIMES%2522%252C%2522durationInMinutes%2522%253A0%252C%2522endTime%2522%253A%25222016-08-11T10%253A01%253A05.000Z%2522%252C%2522startTime%2522%253A%25222016-08-11T09%253A01%253A05.000Z%2522%252C%2522timeRange%2522%253Anull%252C%2522timeRangeAdjusted%2522%253Afalse%257D%257D		
		$sBaseUrl = cAppDynCore::GET_controller();	
		$sTime = self::time_command($poTimes);
		$sFilter = "%257B%2522firstInChain%2522%253Afalse%252C%2522maxRows%2522%253A600%252C%2522applicationIds%25$piAppID%253A%255B16%255D%252C%2522businessTransactionIds%2522%253A%255B3640%255D%252C%2522applicationComponentIds%2522%253A%255B%255D%252C%2522applicationComponentNodeIds%2522%253A%255B%255D%252C%2522errorIDs%2522%253A%255B%255D%252C%2522errorOccured%2522%253Anull%252C%2522userExperience%2522%253A%255B%255D%252C%2522executionTimeInMilis%2522%253Anull%252C%2522endToEndLatency%2522%253Anull%252C%2522url%2522%253Anull%252C%2522sessionId%2522%253Anull%252C%2522userPrincipalId%2522%253Anull%252C%2522dataCollectorFilter%2522%253Anull%252C%2522archived%2522%253Anull%252C%2522guids%2522%253A%255B%255D%252C%2522diagnosticSnapshot%2522%253Anull%252C%2522badRequest%2522%253Anull%252C%2522deepDivePolicy%2522%253A%255B%255D%252C%2522rangeSpecifier%2522%253A%257B%2522type%2522%253A%2522BETWEEN_TIMES%2522%252C%2522durationInMinutes%2522%253A0%252C%2522endTime%2522%253A%25222016-08-11T10%253A01%253A05.000Z%2522%252C%2522startTime%2522%253A%25222016-08-11T09%253A01%253A05.000Z%2522%252C%2522timeRange%2522%253Anull%252C%2522timeRangeAdjusted%2522%253Afalse%257D%257D";
		$sBaseUrl."/#/location=APP_NODE_ALL_SNAPSHOT_LIST&application=$psAppId&node=$piNode&gridFilters=$sFilter&sTime";
	}
	
	private static function time_command($poTimes, $psKey="timeRange"){
		return  "$psKey=Custom_Time_Range.BETWEEN_TIMES.".$poTimes->end.".".$poTimes->start.".0";
	}
	
}
?>
