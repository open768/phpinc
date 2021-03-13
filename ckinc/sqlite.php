<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2021

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
// work in progress
**************************************************************************/
class  cSqlLite {
	const DB_FOLDER = "[dbs]";
	public $dbname = null;
	public $path = null;
	
	public function get_db_path($psDBname){
		global $root;
		return   $root."/".self::$DB_FOLDER."/".psDBname;
	}
	
	public function create_db($psDBname){
		$dbpath = $self->get_db_path($psDBname);
	}
	
	public function open_db( $psDBname){
		$dbpath = $self->get_db_path($psDBname);
	}
}
?>