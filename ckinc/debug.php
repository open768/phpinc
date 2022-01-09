<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/
require_once("$phpinc/ckinc/session.php");

class cDebug{
	private static $DEBUGGING=false;
	private static $EXTRA_DEBUGGING=false;
	public static $IGNORE_CACHE = false;
	private static $aThings = [];
	const DEBUG_STR = "debug";
	const DEBUG2_STR = "debug2";
	const EXTRA_DEBUGGING_SYMBOL = "&#10070";
	
	private static $ENTER_DEPTH = 0;
	
	//##############################################################################
	public static function is_debugging(){
		return (self::$DEBUGGING || self::is_extra_debugging());
	}
	
	public static function is_extra_debugging(){
		return self::$EXTRA_DEBUGGING;
	}
	
	public static function on($pbExtraDebugging = false){

		self::$DEBUGGING=true;
		self::$EXTRA_DEBUGGING = $pbExtraDebugging;
		if (!cSession::is_session_started()) {
			session_start();
			cDebug::extra_debug("session status is not active - starting session:");
		}
		self::write("Debugging on");
		$aCaller = self::pr_get_caller(1);
		if ($aCaller){
			$sFunc = $aCaller['function'];
			self::write("Caller is $sFunc");
		}
	}
	
	public static function off(){
		self::write("Debugging off");
		self::$DEBUGGING=false;
		self::$EXTRA_DEBUGGING = false;
	}
	
	//##############################################################################
	public static function extra_debug_warning($psThing ){
		self::extra_debug("<font color='red'>$psThing</font>");
	}
	
	public static function extra_debug($psThing, $pbOnce=false){
		if (!self::$EXTRA_DEBUGGING) return;
		if ($pbOnce){
			if (array_key_exists($psThing, self::$aThings)) 
				return;
			else
				self::$aThings[$psThing]=1;
		}
		
		$sDate = date('d-m-Y H:i:s');
		?><p><font color='#915c83'><code><?=str_repeat("&nbsp;", self::$ENTER_DEPTH *4)?><?=$sDate?>: <?=self::EXTRA_DEBUGGING_SYMBOL?> <?=$psThing?></code></font><p><?php
		self::flush();
	}
	
	
	public static function write($poThing){
		if (self::is_debugging()){
			$sDate = date('d-m-Y H:i:s');
			?><p><font color='#3b3b6d'><code><?=str_repeat("&nbsp;", self::$ENTER_DEPTH *4)?><?=$sDate?>: <?=$poThing?></code></font><p><?php
			self::flush();
		}
	}
		
	public static function flush(){
		@ob_flush();
		@flush();
	}
	
	//**************************************************************************
	public static function vardump( $poThing, $pbForce=false){
		if (self::$EXTRA_DEBUGGING || (self::$DEBUGGING && $pbForce)){
			echo "<table border=1 width=100%><tr><td><PRE>";
			var_dump($poThing);
			echo "</PRE></td></tr></table>";
			self::flush();
		}else
			self::write(__FUNCTION__." only available in debug2");
	}

	//**************************************************************************
	public static function error($psText){
		try{
			$aCaller = self::pr_get_caller(1);
			$sFunc = @$aCaller['function'];
			$sClass = @$aCaller['class'];
			$sLine = @$aCaller['line'];
		}
		catch (Exception $e)
		{}
		self::write("<b><font size='+2'>in <font color='brick'>$sClass:$sFunc (line $sLine)</font> error: <font color='brick'>$psText</font></font></b><pre>");
		throw new Exception($psText);
	}
	//**************************************************************************
	public static function warning($psText){
		self::write("<b><font size='+2'color='brick'>Warning:</font></b> $psText");
	}
	
	//*******************************************************************
	public static function is_localhost(){
		$aList = array(
			'127.0.0.1',
			'::1'
		);

		$sServer = $_SERVER['REMOTE_ADDR'];
		$bLocal = in_array($sServer, $aList);
		cDebug::write("Server: '$sServer', local: $bLocal");
		return $bLocal;
	}
	
	//##############################################################################
	public static function check_GET_or_POST(){
		global $_GET, $_POST, $_SERVER;
		
		if (isset($_GET[self::DEBUG_STR]) || isset($_POST[self::DEBUG_STR])){
			self::on();
			self::write("Debugging is on");
		}
		
		
		if (isset($_GET[self::DEBUG2_STR]) || isset($_POST[self::DEBUG2_STR])){
			self::on(true);
			self::write("Extra debugging is on - shown by ".self::EXTRA_DEBUGGING_SYMBOL);
			self::write("URI is ".$_SERVER["REQUEST_SCHEME"]."://".$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"]);			
		}elseif (self::$DEBUGGING){
			self::write("for extra debugging use debug2");
		}
		
		if (isset($_GET["nocache"]) || isset($_POST["nocache"])){
			if (!self::$DEBUGGING) self::error("cant use nocache without debug");
				
			self::$IGNORE_CACHE = true;
			self::write("ignore cache is on");
		}else
			self::write("nocache option is available");
	}
	
	//##############################################################################
	public static function enter( $psOverrideName = null, $pbOnce=false){
		if (self::$EXTRA_DEBUGGING){
			$sCaller = $psOverrideName;
			if ($psOverrideName == null){
				$aCaller = self::pr_get_caller(1);
				$sFunc = $aCaller['function'];
				$sClass = '';
				if ( isset($aCaller['class'])){
					$sClass = $aCaller['class'];
				}
				$sCaller = "$sClass.$sFunc";
			}
			
			self::extra_debug("<font color='#3b3c36' face='courier' size=2>Enter&gt; $sCaller</font>", $pbOnce);
			self::$ENTER_DEPTH++;
		}
	}

	//**************************************************************************
	public static function leave($psOverrideName = null, $pbOnce=false){
		if (self::$EXTRA_DEBUGGING){
				
			$sCaller = $psOverrideName;
			if ($psOverrideName == null){
				$aCaller = self::pr_get_caller(1);
				$sFunc = $aCaller['function'];
				$sClass = '';
				if (isset($aCaller['class']))	{
					$sClass = $aCaller['class'];
				}
				$sCaller = "$sClass.$sFunc";
			}
			self::$ENTER_DEPTH--;
			if (self::$ENTER_DEPTH < 0) {
				self::$ENTER_DEPTH = 0;
				//self::warning("too many leave calls");
			}
			self::extra_debug("<font color='#3b3c36' face='courier' size=2>Leave &gt; $sCaller</font>", $pbOnce);
		}
	}
	
	//##############################################################################
	private static function pr_get_caller( $piLimit=0){
		return @debug_backtrace(0,$piLimit+2)[$piLimit+1];
	}
	//**************************************************************************
	private static function pr_stacktrace(){
		if (self::$EXTRA_DEBUGGING || (self::$DEBUGGING && $pbForce)){
			echo "<pre>";
			debug_print_backtrace();
			echo "</pre>";
		}else
			self::write(__FUNCTION__." only available in debug2");
	}
}