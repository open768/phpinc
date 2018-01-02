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
require_once("$phpinc/appdynamics/common.php");
require_once("$phpinc/appdynamics/metrics.php");

//#################################################################
//# CLASSES
//#################################################################

class cAppDynDemo{
	public static function GET_Applications(){
		cDebug::write("generating demo applications");
		$aData = [];
		for ($i=1; $i<5; $i++){
			$oApp = new cAppDApp("Application ".$i, $i);
			array_push($aData, $oApp);
		}
		return $aData;
	}
	
	//*****************************************************************
	public static function GET_MetricData($psApp, $psMetricPath, $poTimes , $psRollup=false, $pbCacheable=false, $pbMulti = false){
		$aOutput = [];
		
		$epoch_start = $poTimes->start;
		$epoch_end = $poTimes->end;
		
		$iDTime = ($epoch_end - $epoch_start)/100;
		$iVal = 100;
		for ($i = $epoch_start; $i<=$epoch_end; $i+=$iDTime){
			$oRow = new cAppdynMetricRow;
			$oRow->startTimeInMillis = $i;
			$oRow->value = $iVal;
			$oRow->max = $iVal;
			
			$iVal ++;
			array_push( $aOutput, $oRow);
		}
		
		return $aOutput;
	}
}
?>
