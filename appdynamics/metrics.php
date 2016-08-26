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

//#################################################################
//# CLASSES
//#################################################################
class cAppDynMetric{
	const USAGE_METRIC = "moduleusage";
	const RESPONSE_TIME = "Average Response Time (ms)";
	const CALLS_PER_MIN = "Calls per Minute";
	const ERRS_PER_MIN = "Errors per Minute";
	
	const APPLICATION = "Overall Application Performance";
	const INFRASTRUCTURE = "Application Infrastructure Performance";
	const BACKENDS = "Backends";
	const TRANSACTIONS = "Business Transaction Performance";
	const END_USER = "End User Experience";
	const DATABASES = "Databases";
	const INFORMATION_POINTS = "Information Points";
	const SERVICE_END_POINTS = "Service Endpoints";
	const EXT_CALLS = "External Calls";
	const BASE_PAGES = "Base Pages";
	const AJAX_REQ = "AJAX Requests";
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Module Usage
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function moduleUsage($psModule, $piMonths){
		return self::USAGE_METRIC."/$psModule/$piMonths";
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Application
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function appResponseTimes(){
		return self::APPLICATION."|".self::RESPONSE_TIME;
	}
	
	public static function appCallsPerMin(){
		return self::APPLICATION."|".self::CALLS_PER_MIN;
	}

	public static function appSlowCalls(){
		return self::APPLICATION."|Number of Slow Calls";
	}

	public static function appVerySlowCalls(){
		return self::APPLICATION."|Number of Very Slow Calls";
	}

	public static function appStalledCount(){
		return self::APPLICATION."|Stall Count";
	}
	public static function appErrorsPerMin(){
		return self::APPLICATION."|".self::ERRS_PER_MIN;
	}
	public static function appExceptionsPerMin(){
		return self::APPLICATION."|Exceptions per Minute";
	}

	public static function appBackends(){
		return self::backends();
	}

	

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Service End  Points
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function endPointResponseTimes($psTier, $psName){
		return self::SERVICE_END_POINTS."|$psTier|$psName|".self::RESPONSE_TIME;
	}
	
	public static function endPointCallsPerMin($psTier, $psName){
		return self::SERVICE_END_POINTS."|$psTier|$psName|".self::CALLS_PER_MIN;
	}

	public static function endPointErrorsPerMin($psTier, $psName){
		return self::SERVICE_END_POINTS."|$psTier|$psName|".self::ERRS_PER_MIN;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* information Points
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function infoPointResponseTimes($psName){
		return self::INFORMATION_POINTS."|$psName|".self::RESPONSE_TIME;
	}
	
	public static function infoPointCallsPerMin($psName){
		return self::INFORMATION_POINTS."|$psName|".self::CALLS_PER_MIN;
	}

	public static function infoPointErrorsPerMin($psName){
		return self::INFORMATION_POINTS."|$psName|".self::ERRS_PER_MIN;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Database
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function databases(){
		return self::DATABASES;
	}
	
	public static function databaseKPI($psDB){
		return self::DATABASES."|$psDB|KPI";
	}
	
	public static function databaseTimeSpent($psDB){
		return self::databaseKPI($psDB)."|Time Spent in Executions (s)";
	}
	public static function databaseConnections($psDB){
		return self::databaseKPI($psDB)."|Number of Connections";
	}
	public static function databaseCalls($psDB){
		return self::databaseKPI($psDB)."|".self::CALLS_PER_MIN;
	}

	public static function databaseServerStats($psDB){
		return self::DATABASES."|$psDB|Server Statistic";
	}
	
	
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* webrum
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function webrumCallsPerMin(){
		return self::END_USER."|App|Page Requests per Minute";
	}
	public static function webrumResponseTimes(){
		return self::END_USER."|App|End User Response Time (ms)";
	}
	public static function webrumFirstByte(){
		return self::END_USER."|App|First Byte Time (ms)";
	}
	public static function webrumServerTime(){
		return self::END_USER."|App|Application Server Time (ms)";
	}
	public static function webrumTCPTime(){
		return self::END_USER."|App|TCP Connect Time (ms)";
	}

	public static function webrumAjax(){
		return self::END_USER."|Base Pages";
	}
	public static function webrumPages(){
		return self::END_USER."|Base Pages";
	}
	
	public Static function webRumMetric($psKind, $psPage, $psMetric)
	{
		switch ($psKind){
			case self::BASE_PAGES:
			case self::AJAX_REQ:
				break;
			default:
				cDebug::error("unknown kind");
		}
		return self::END_USER."|$psKind|$psPage|$psMetric";
	}
	
	
	public static function webrumPageCallsPerMin($psType, $psPage){
		return self::webRumMetric($psType, $psPage, "Requests per Minute");
	}
	public static function webrumPageResponseTimes($psType, $psPage){
		return self::webRumMetric($psType, $psPage, "End User Response Time (ms)");
	}
	public static function webrumPageFirstByte($psType, $psPage){
		return self::webRumMetric($psType, $psPage, "First Byte Time (ms)");
	}
	public static function webrumPageServerTime($psType, $psPage){
		return self::webRumMetric($psType, $psPage, "Application Server Time (ms)");
	}
	public static function webrumPageTCPTime($psType, $psPage){
		return self::webRumMetric($psType, $psPage, "TCP Connect Time (ms)");
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* backends
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function backends(){
		return self::BACKENDS;
	}
	
	public static function backendResponseTimes($psBackend){
		return self::BACKENDS."|$psBackend|".self::RESPONSE_TIME;
	}

	public static function backendCallsPerMin($psBackend){
		return self::BACKENDS."|$psBackend|".self::CALLS_PER_MIN;
	}
	public static function backendErrorsPerMin($psBackend){
		return self::BACKENDS."|$psBackend|".self::ERRS_PER_MIN;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* transactions
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function transMetric($psTier, $psTrans, $psNode=null){
		$sMetric = self::tierTransactions($psTier)."|$psTrans";
		if ($psNode) $sMetric .= "|Individual Nodes|$psNode";
		return $sMetric;
	}
	
	public static function transResponseTimes($psTier, $psTrans, $psNode=null){
		return self::transMetric($psTier, $psTrans, $psNode)."|".self::RESPONSE_TIME;
	}

	public static function transErrors($psTier, $psTrans, $psNode=null){
		return self::transMetric($psTier, $psTrans, $psNode)."|".self::ERRS_PER_MIN;
	}

	public static function transCpuUsed($psTier, $psTrans, $psNode=null){
		return self::transMetric($psTier, $psTrans, $psNode)."|Average CPU Used (ms)";
	}
	
	public static function transCallsPerMin($psTier, $psTrans, $psNode=null){
		return self::transMetric($psTier, $psTrans, $psNode)."|".self::CALLS_PER_MIN;
	}
	
	public static function transExtNames($psTier, $psTrans, $psNode=null){
		return self::transMetric($psTier, $psTrans, $psNode)."|".self::EXT_CALLS;
	}
	
	public static function transExtCalls($psTier, $psTrans, $psOther){
		return self::transExtNames($psTier, $psTrans)."|$psOther|".self::CALLS_PER_MIN;
	}
		
	public static function transExtResponseTimes($psTier, $psTrans, $psOther){
		return self::transExtNames($psTier, $psTrans)."|$psOther|".self::RESPONSE_TIME;
	}
		
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* tiers
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function tier($psTier){
		return self::TRANSACTIONS."|Business Transactions|$psTier";
	}
	
	public static function tierCallsPerMin($psTier){
		return self::APPLICATION."|$psTier|".self::CALLS_PER_MIN;
	}
	public static function tierResponseTimes($psTier){
		return self::APPLICATION."|$psTier|".self::RESPONSE_TIME;
	}
	public static function tierErrorsPerMin($psTier){
		return self::APPLICATION."|$psTier|".self::ERRS_PER_MIN;
	}
	public static function tierExceptionsPerMin($psTier){
		return self::APPLICATION."|$psTier|Exceptions per Minute";
	}
	public static function tierSlowCalls($psTier){
		return self::APPLICATION."|$psTier|Number of Slow Calls";
	}
	
	public static function tierNodes($psTier){
		return self::APPLICATION."|$psTier|Individual Nodes";
	}

	public static function tierVerySlowCalls($psTier){
		return self::APPLICATION."|$psTier|Number of Very Slow Calls";
	}
	public static function tierTransactions($psTier){
		$sMetric = self::TRANSACTIONS."|Business Transactions|$psTier";
		return $sMetric;
	}
	
	public static function tierExtCallsPerMin($psTier1,$psTier2){
		return self::APPLICATION."|$psTier1|".self::EXT_CALLS."|$psTier2|".self::CALLS_PER_MIN;
	}

	public static function tierExtResponseTimes($psTier1,$psTier2){
		return self::APPLICATION."|$psTier1|".self::EXT_CALLS."|$psTier2|".self::RESPONSE_TIME;
	}

	public static function tierNodeCallsPerMin($psTier, $psNode=null){
		if ($psNode)
			return self::tierNodes($psTier)."|$psNode|".self::CALLS_PER_MIN;
		else
			return self::tierCallsPerMin($psTier);
	}
	
	public static function tierNodeResponseTimes($psTier, $psNode=null){
		if ($psNode)
			return self::tierNodes($psTier)."|$psNode|".self::RESPONSE_TIME;
		else
			return self::tierResponseTimes($psTier);
	}
	
	public static function tierServiceEndPoints($psTier){
		return self::SERVICE_END_POINTS. "|$psTier";
	}

	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* infrastructure
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function InfrastructureNodes($psTier){
		return self::INFRASTRUCTURE."|$psTier|Individual Nodes";
	}
	
	public static function InfrastructureNode($psTier, $psNode= null){
		$sMetric = self::INFRASTRUCTURE."|$psTier";
		if ($psNode) $sMetric .= "|Individual Nodes|$psNode";
		
		return $sMetric;
	}
	
	public static function InfrastructureJDBCPools($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JMX|JDBC Connection Pools";
	}

	public static function InfrastructureJDBCPoolActive($psTier, $psNode=null, $psPool){
		return self::InfrastructureJDBCPools($psTier, $psNode)."|$psPool|Active Connections";
	}
	public static function InfrastructureJDBCPoolMax($psTier, $psNode=null, $psPool){
		return self::InfrastructureJDBCPools($psTier, $psNode)."|$psPool|Maximum Connections";
	}

	public static function InfrastructureNodeDisks($psTier, $psNode){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Disks";
	}
	public static function InfrastructureNodeDiskFree($psTier, $psNode, $psDisk){
		return self::InfrastructureNodeDisks($psTier, $psNode)."|$psDisk|Space Available";
	}
	
	public static function InfrastructureAgentAvailability($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Agent|Machine|Availability";
	}

	public static function InfrastructureAgentMetricsUploaded($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Agent|Metric Upload|Metrics uploaded";
	}

	public static function InfrastructureAgentMetricsLicenseErrors($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."Agent|Metric Upload|Requests License Errors";
	}

	public static function InfrastructureAgentInvalidMetrics($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."Agent|Metric Upload|Invalid Metrics";
	}

	public static function InfrastructureMachineAvailability($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Machine|Availability";
	}

	public static function InfrastructureCpuBusy($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|CPU|%Busy";
	}

	public static function InfrastructureMemoryFree($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Memory|Free (MB)";
	}

	public static function InfrastructureDiskFree($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Disks|MB Free";
	}

	public static function InfrastructureNetworkIncoming($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Network|Incoming KB/sec";
	}
	public static function InfrastructureNetworkOutgoing($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Network|Outgoing KB/sec";
	}
	
	public static function InfrastructureJavaHeapUsed($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JVM|Memory|Heap|Current Usage (MB)";
	}
	public static function InfrastructureJavaGCTime($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JVM|Garbage Collection|GC Time Spent Per Min (ms)";
	}
	public static function InfrastructureJavaCPUUsage($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JVM|Process CPU Usage %";
	}
	public static function InfrastructureDotnetHeapUsed($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|CLR|Memory|Heap|Current Usage (bytes)";
	}
	public static function InfrastructureDotnetGCTime($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|CLR|Garbage Collection|GC Time Spent (%)";
	}
	public static function InfrastructureDotnetAnonRequests($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|ASP.NET Applications|Anonymous Requests";
	}
	
	public static function InfrastructureMetric($psTier, $psNode, $psMetric){
		return self::InfrastructureNode($psTier, $psNode)."|$psMetric";
	}
	
}
?>
