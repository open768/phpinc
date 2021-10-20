<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013-2021 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//#######################################################################
//#######################################################################
class cRenderW3{

	//**************************************************************************
	public static function tag($psCaption, $psColour="w3-light-grey", $psExtraClass=""){
		return  "<span class='w3-tag  $psColour w3-round w3-border $psExtraClass' style='text-align:left'>".htmlspecialchars($psCaption)."</span> ";
	}
	
	public static function panel_start($psExtraClasses){
		return "<div class='w3-panel $psExtraClasses'>";
	}
}
?>
