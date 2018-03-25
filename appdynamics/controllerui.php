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
	//###############################################################################################
	public static function home(){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=AD_HOME";
	}	
	private static function time_command($poTimes, $psKey="timeRange"){
		return  "$psKey=Custom_Time_Range.BETWEEN_TIMES.".$poTimes->end.".".$poTimes->start.".0";
	}

	//###############################################################################################
	public static function agents(){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=SETTINGS_AGENTS";

	}
	public static function licenses(){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=SETTINGS_LICENSE";
	}
	
	//###############################################################################################
	public static function apps_home(){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APPS_ALL_DASHBOARD";
	}
	public static function application($poApp){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_DASHBOARD&application=$poApp->id";
	}
	public static function app_slow_transactions($poApp){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_SLOW_TRANSACTIONS&application=$poApp->id";
	}
		
	//###############################################################################################
	//# Databases
	public static function databases(){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=DB_MONITORING_SERVER_LIST";
	}
	
	//###############################################################################################
	//# Events
	public static function events($poApp){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=APP_EVENTSTREAM_LIST&application=$poApp->id";
	}

	public static function event_detail($piEventID){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=APP_EVENT_VIEWER_MODAL&eventSummary=$piEventID";
	}
	
	//###############################################################################################
	//# Nodes
	public static function nodes($poApp){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_INFRASTRUCTURE&application=$poApp->id&appServerListMode=grid";
	}

	public static function nodeDashboard($poApp, $piNodeID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_NODE_MANAGER&application=$poApp->id&node=$piNodeID&dashboardMode=force";
	}
	
	public static function nodeAgent($poApp, $piNode){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."#/location=APP_NODE_AGENTS&application=$poApp->id&bypassAssociatedLocationsCheck=true&tab=10&node=$piNode&memoryViewMode=0";
	}
	
	public static function machineDetails($piMachineID){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=INFRASTRUCTURE_MACHINE_DETAIL&machine=$piMachineID";
	}

	//###############################################################################################
	//# Remote services
	public static function remoteServices($poApp){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_BACKEND_LIST&application=$poApp->id";
	}

	//###############################################################################################
	//# Tiers
	public static function tier_errors($poApp, $poTier){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_TIER_ERROR_TRANSACTIONS&application=$poApp->id&component=$poTier->id";
	}
	
	public static function tier_slow_transactions($poApp, $poTier){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_TIER_SLOW_TRANSACTIONS&application=$poApp->id&component=$poTier->id";
	}
	public static function tier_slow_remote($poApp, $poTier){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_TIER_SLOW_DB_REMOTE_SERVICE_CALLS&application=$poApp->id&component=$poTier->id";
	}
	
	public static function tier($poApp, $poTier){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_COMPONENT_MANAGER&application=$poapp->id&component=$poTier->id&dashboardMode=force";
	}
	
	//###############################################################################################
	//# Transactions
	public static function businessTransactions($poApp){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_BT_LIST&application=$poApp->id";
	}
	
	public static function transaction($poApp, $piTransID){
		$sBaseUrl = cAppDynCore::GET_controller();
		return $sBaseUrl."/#/location=APP_BT_DETAIL&application=$poApp->id&businessTransaction=$piTransID&dashboardMode=force";
	}

	//###############################################################################################
	//# snapshots
	
	public static function snapshot($poApp, $piTransID, $psGuid, $poTimes){
		$sBaseUrl = cAppDynCore::GET_controller();	
		$sTimeRange = self::time_command($poTimes);
		$sTimeRSD = self::time_command($poTimes, "rsdTime");
		return $sBaseUrl."/#/location=APP_SNAPSHOT_VIEWER&$sTimeRange&application=$poApp->id&bypassAssociatedLocationsCheck=true&tab=1&businessTransaction=$piTransID&requestGUID=$psGuid&$sTimeRSD&dashboardMode=force";
	}
	
	public static function transaction_snapshots($poApp, $piTransID, $poTimes){
		$sBaseUrl = cAppDynCore::GET_controller();	
		$sTime = self::time_command($poTimes);
		return $sBaseUrl."/#/location=APP_BT_ALL_SNAPSHOT_LIST&application=$poApp->id&bypassAssociatedLocationsCheck=true&tab=1&businessTransaction=$piTransID&$sTime";
	}
	
	//###############################################################################################
	public static function webrum($poApp){
		$sBaseUrl = cAppDynCore::GET_controller();	
		return $sBaseUrl."/#/location=APP_EUM_WEB_MAIN_DASHBOARD&application=$poApp->id";
	}
			
}
?>
