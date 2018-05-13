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
require_once("$phpinc/appdynamics/appdynamics.php");

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
   
	//*****************************************************************
	//*****************************************************************
	public function GET_transaction_names(){
		//find out the transactions in this tier - through metric heirarchy (but doesnt give the trans IDs)
		cDebug::enter();
		$aResults = []; 
		
		try{
			$metricPath = cAppDynMetric::tierTransactions($this->name);
			$aTierTransactions = cAppdynCore::GET_Metric_heirarchy($this->app->name, $metricPath, false);	
			if (!$aTierTransactions) return null;
			
			//so get the transaction IDs
			$aAppTrans= cAppdynUtil::get_trans_assoc_array($this->app);
			
			// and combine the two

			foreach ($aTierTransactions as $oTierTrans){
				if (!array_key_exists($oTierTrans->name, $aAppTrans)) continue;
				
				$sTransID = $aAppTrans[$oTierTrans->name];
				$oDetail = new cAppDDetails($oTierTrans->name, $sTransID, null, null);
				$aResults[] = $oDetail;
			}
			
			uasort($aResults, 'ad_sort_by_name');
		}
		catch (Exception $e){
			$aResults = null;
		}
		cDebug::leave();
		return $aResults;
	}
	
   
}
?>
