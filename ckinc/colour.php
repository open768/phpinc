<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2016 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

require_once("$phpinc/ckinc/debug.php");

class cColour{
	public static function multigradient($paColours){
		$aOut = [];
		
		for ($i=1; $i<count($paColours); $i++){
			$a1 = $paColours[ $i-1];
			$a2 = $paColours[ $i];
			$aPart = self::lineargradient($a1[0],$a1[1],$a1[2],$a2[0],$a2[1],$a2[2],$a1[3]);
			foreach ($aPart as $sColour)	
				$aOut[] = $sColour;
		}
		
		return $aOut;
	}
	
	public static function showGradient($paColours, $psText="&nbsp;", $piWidth=30){
		$aOut = [];
		
		echo "<table><tr>";
			foreach ($paColours as $sColour) 
				echo "<td bgcolor='$sColour' width='$piWidth'>$psText</td>";
		echo "</tr></table>";
	}
	
	public static function lineargradient($r1,$g1,$b1,$r2,$g2,$b2,$steps) {
		$colorindex = array();
		$dr = ($r2-$r1)/$steps;
		$dg = ($g2-$g1)/$steps;
		$db = ($b2-$b1)/$steps;
		
		$r=$r1;$b=$b1;$g=$g1;
		$colorindex[] = "#".dechex(intval($r)).dechex(intval($g)).dechex(intval($b));
		for($i=1; $i<$steps; $i++) {
			$r+= $dr;
			$g+= $dg;
			$b+= $db;
			$colorindex[] = "#".dechex(intval($r)).dechex(intval($g)).dechex(intval($b));
		}
		return $colorindex;
	}
}