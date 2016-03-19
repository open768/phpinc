<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

class cDebug{
	public static $DEBUGGING=false;
	public static $EXTRA_DEBUGGING=false;
	private static $ENTER_DEPTH = 0;
	
	public static function is_debugging(){
		return (self::$DEBUGGING || self::$EXTRA_DEBUGGING);
	}
	
	public static function on($pbExtraDebugging = false){
		self::$DEBUGGING=true;
		self::$EXTRA_DEBUGGING = $pbExtraDebugging;
		self::write("Debugging on");
	}
	public static function off(){
		self::write("Debugging off");
		self::$DEBUGGING=false;
		self::$EXTRA_DEBUGGING = false;
	}
	
	public static function extra_debug($poThing){
		if (self::$EXTRA_DEBUGGING){
			$sDate = date('d-m-Y H:i:s');
			echo "<p><font color=red><code>** $sDate: $poThing</code></font><p>";
			ob_flush();
			flush();
		}
	}
	public static function write($poThing){
		if (self::$DEBUGGING){
			$sDate = date('d-m-Y H:i:s');
			echo "<p><font color=red><code>$sDate: $poThing</code></font><p>";
			ob_flush();
			flush();
		}
	}
		
	//**************************************************************************
	public static function vardump( $poThing, $pbForce=false){
		if (self::$EXTRA_DEBUGGING || (self::$DEBUGGING && $pbForce)){
			echo "<table border=1 width=100%><tr><td><PRE>";
			var_dump($poThing);
			echo "</PRE></td></tr></table>";
			ob_flush();
			flush();
		}else
			self::write(__FUNCTION__." only available in debug2");
	}

	//**************************************************************************
	public static function error($psText){
		$aCaller = self::get_caller(1);
		$sFunc = $aCaller['function'];
		$sClass = $aCaller['class'];
		$sLine = $aCaller['line'];
		self::write("<b><font size='+2'>in <font color='brick'>$sClass:$sFunc (line $sLine)</font> error: <font color='brick'>$psText</font></font></b><pre>");
		throw new Exception($psText);
	}
	
	//**************************************************************************
	public static function check_GET_or_POST(){
		global $_GET, $_POST;
		
		if (isset($_GET["debug"]) || isset($_POST["debug"])){
			self::$DEBUGGING = true;
			self::write("Debugging is on");
		}
		
		if (isset($_GET["debug2"]) || isset($_POST["debug2"])){
			self::$EXTRA_DEBUGGING = true;
			self::$DEBUGGING = true;
		}elseif (self::$DEBUGGING){
			self::write("for extra debugging use debug2");
		}
		
	}
	
	public static function stacktrace(){
		if (self::$EXTRA_DEBUGGING || (self::$DEBUGGING && $pbForce)){
			echo "<pre>";
			debug_print_backtrace();
			echo "</pre>";
		}else
			self::write(__FUNCTION__." only available in debug2");
	}
	
	public static function enter( ){
		if (self::$EXTRA_DEBUGGING){
			$aCaller = self::get_caller(1);
			$sFunc = $aCaller['function'];
			$sClass = $aCaller['class'];
			$padding = 
			self::extra_debug("Enter ".str_repeat("----", self::$ENTER_DEPTH). "> $sClass.$sFunc");
			self::$ENTER_DEPTH++;
		}
	}

	public static function leave(){
		if (self::$EXTRA_DEBUGGING){
			self::$ENTER_DEPTH--;
			$aCaller = self::get_caller(1);
			$sFunc = $aCaller['function'];
			$sClass = $aCaller['class'];
			self::extra_debug("Leave ".str_repeat("----", self::$ENTER_DEPTH). "> $sClass.$sFunc");
		}
	}
	
	public static function get_caller( $piLimit=0){
		return debug_backtrace(0,$piLimit+2)[$piLimit+1];
	}
}