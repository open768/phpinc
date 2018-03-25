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
	public static function GET_snapshot_segments($psGUID){
		$sTimeUrl = cAppdynUtil::controller_short_time_command( self::$oTimes);
		$sURL = "snapshot/getRequestSegmentData?requestGUID=$psGUID&$sTimeUrl";
		return  cAppdynCore::GET_restUI($sURL);
	}
}
