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
		return $sBaseUrl."/#/location=APP_BT_DETAIL&application=$piAppID&bypassAssociatedLocationsCheck=true&tab=1&businessTransaction=$piTransID&$sTime";
	}
	
	private static function time_command($poTimes, $psKey="timeRange"){
		return  "$psKey=Custom_Time_Range.BETWEEN_TIMES.".$poTimes->end.".".$poTimes->start.".0";
	}
	
}
?>
