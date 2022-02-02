<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2018

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
//
//
**************************************************************************/

require_once("$phpinc/ckinc/debug.php");

//########################################################################
//#
//########################################################################
class cAssocArray{
	private $data = [];
	
	public function get($psKey){
		if (isset($this->data[$psKey]))
			return $this->data[$psKey];
		else	
			return null;
	}
	
	public function set($psKey, $pvValue){
		$this->data[$psKey] = $pvValue;
	}
	public function keys(){
		return array_keys($this->data);
	}
	public function key_exists($psKey){
		return isset($this->data[$psKey]);
	}
	
	public function count(){
		return count($this->data);
	}
}

//########################################################################
//#
//########################################################################
class c2DArray{
	private $caRows = null;
	private $caRowNames = null;
	private $caColNames = null;
	private $caColInfo = null;

	// *************************** constructor ***************************
    public function __construct(){
		$this->caRows = new cAssocArray;
		$this->caRowNames = new cAssocArray;
		$this->caColNames = new cAssocArray;
		$this->caColInfo = new cAssocArray;
	}

	//********************************************************************
	private function pr__get_row($psRow, $pbCreate = false){
		if (!$this->caRowNames->key_exists($psRow))
			if ($pbCreate){
				$this->caRowNames->set($psRow,1);
				$this->caRows->set($psRow, new cAssocArray);
			}else
				return null;
		return $this->caRows->get($psRow);
	}
	
	//********************************************************************
	public function colNames(){
		return $this->caColNames->keys();
	}
	
	//********************************************************************
	public function rowNames(){
		return $this->caRowNames->keys();
	}
	
	//********************************************************************
	public function length(){
		return count($this->rowNames());
	}
	
	//********************************************************************
	public function get($psRow, $psCol){
		$oRow = $this->pr__get_row($psRow, false);
		if ($oRow == null) return null;
		
		return $oRow->get($psCol);
	}
	
	//********************************************************************
	public function set($psRow, $psCol, $pvData){
		cDebug::enter();
		
		$oRow = $this->pr__get_row($psRow, true);
		$oRow->set($psCol,$pvData);
		
		if (!$this->caColNames->key_exists($psCol))		$this->caColNames->set($psCol,1);
		
		cDebug::leave();
	}
	
	//********************************************************************
	public function add_col_data_array($psCol, $paData){
		$aRowNames = array_keys($paData);
		foreach ( $aRowNames as $sRow)
			$this->set($sRow, $psCol, $paData[$sRow]);
	}
	
	//********************************************************************
	public function add_col_data_obj($psCol, $poData){
		$sClass = get_class($poData);
		if ($sClass !== "cAssocArray") cDebug::error("unexpected class type: $sClass");
		
		$aRowNames = $poData->keys();
		foreach ( $aRowNames as $sRow)
			$this->set($sRow, $psCol, $poData->get($sRow));
	}
	
	//********************************************************************
	public function add_col_data($psCol, $pvData){
		cDebug::enter();
		$sType = gettype($pvData);
		switch( $sType){
			case "array":
				self::add_col_data_array($psCol, $pvData);
				break;
			case "object":
				self::add_col_data_obj($psCol, $pvData);
				break;
			default:
				cDebug::error("unexpected type: $sType");
		}
		cDebug::leave();
	}
	
	public function set_col_info($psCol, $pvInfo){
		$this->caColInfo->set($psCol,$pvInfo);
	}
	public function get_col_info($psCol){
		return $this->caColInfo->get($psCol);
	}
}

class cArrayUtil {
	public static function array_is_empty( $paInput){
		return ( $paInput == null  || count($paInput) ==0);
	} 
}
