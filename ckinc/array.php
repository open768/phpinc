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

class c2DArray{
	private $caRows = [];
	private $caRowNames = [];
	private $caColNames = [];

	//********************************************************************
	private function pr__get_row($psRow, $pbCreate = false){
		if (!array_key_exists($psRow, $this->caRowNames))
			if ($pbCreate){
				$this->caRowNames[$psRow] = 1;
				$this->caRows[$psRow] = [];
			}else
				return null;
		return $this->caRows[$psRow];
	}
	
	//********************************************************************
	public function get_colnames(){
		return $this->caColNames;
	}
	
	//********************************************************************
	public function get_rownames(){
		return $this->caRowNames;
	}
	
	//********************************************************************
	public function get($psRow, $psCol){
		$aRow = $this->pr__get_row($psRow, false);
		if ($aRow == null) return null;
		
		if (!array_key_exists($psCol, $aRow))
			return null;
		else
			return $aRow[$psCol];
	}
	
	//********************************************************************
	public function set($psRow, $psCol, $pvData){
		$aRow = $this->pr__get_row($psRow, true);
		if (!array_key_exists($psRow, $this->caColNames))
			$this->caColNames[$psCol] = 1;
		$aRow[$psCol] = $pvData;
	}
}
