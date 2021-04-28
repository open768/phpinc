<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2021

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk
or leave a message on github

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
//
//% OBJSTOREDB - simplistic store objects without a relational database!
//%
//% solves Problem -  thousands files on busy websites that exceed inode quotas.
//%
**************************************************************************/

require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/ckinc/gz.php");
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/ckinc/sqlite.php");

//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//%% Database 
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// for sqllite3 quickstart see https://riptutorial.com/php/example/27461/sqlite3-quickstart-tutorial
// THIS IS A WORK IN PROGRESS objstoredb is not being used
class cOBjStoreDB{
	private static $warned_oldstyle = false;
	private static $database = null; //static as same database obj used between instances
	public $SHOW_SQL = false;
	
	private static $oSQLite = null;
	public $rootFolder = null;
	public $realm = null;
	public $check_expiry = false;
	public $expire_time = null;
	public $table = null;
	
	const DB_FILENAME = "objstore.db";
	const TABLE_NAME = "objstore";
	const COL_REALM = "RE";
	const COL_HASH = "HA";
	const COL_CONTENT = "CO";
	const COL_DATE = "DA";
	const OBJSTORE_REALM = "_objstore_";
	const OBJSTORE_CREATE_KEY = "created on";

	
	//#####################################################################
	//# constructor
	//#####################################################################
    function __construct() {
		global $root;
		cDebug::enter();
		$this->table = self::TABLE_NAME;
		if (self::$oSQLite == null){
			cDebug::extra_debug("creating cSqlLite instance");
			$oDB = new cSqlLite(self::DB_FILENAME);
			self::$oSQLite = $oDB;
			$this->pr_create_table();
		}else
			cDebug::extra_debug(" cSqlLite instance exists");
		cDebug::leave();
    }
	
	
	//#####################################################################
	//# PRIVATES
	//#####################################################################
	
	private function pr_create_table(){
		cDebug::enter();
		$oSQL = self::$oSQLite;
		
		
		//check if table exists
		cDebug::extra_debug("checking table exists");				
		$sSQL = 'SELECT name FROM sqlite_master WHERE name=":t"';
		$sSQL = str_replace(":t",$this->table, $sSQL);
		if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);
		$oResultSet = $oSQL->query($sSQL);
		if ($oResultSet == null) cDebug::error("null response: $sSQL");
			
		$aResults = $oResultSet->fetchArray();
		if ( $aResults ){
			cDebug::extra_debug("table does exist");				
			//cDebug::vardump($aResults); //DEBUG
			cDebug::leave();
			return;
		}
		
		//table doesnt exist
		cDebug::extra_debug("table does not exist");				
		$sSQL = 'CREATE TABLE ":t" ( ":r" TEXT not null, ":h" TEXT not null, ":c" TEXT, ":d" DATETIME DEFAULT CURRENT_TIMESTAMP, primary key ( ":r", ":h"))';
		$sSQL = str_replace(":t",$this->table, $sSQL);
		$sSQL = str_replace(":r",self::COL_REALM, $sSQL);
		$sSQL = str_replace(":h",self::COL_HASH, $sSQL);
		$sSQL = str_replace(":c",self::COL_CONTENT, $sSQL);
		$sSQL = str_replace(":d",self::COL_DATE, $sSQL);		
		if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);

		$oStmt = $oSQL->query($sSQL);
		cDebug::extra_debug("table created");				
		
		//add a timestamp to say when this database was created
		cDebug::extra_debug("writing creation timestamp");				
		$sNow = date('d-m-Y H:i:s');
		$oObj = new cOBjStoreDB();
		$oObj->realm = self::OBJSTORE_REALM;
		$oObj->put( self::OBJSTORE_CREATE_KEY, $sNow);
		cDebug::leave();
	}
	
	//********************************************************************************
	private function pr_check_realm(){
		if ($this->realm == null) cDebug::error("realm is null");
	}
	
	//********************************************************************************
	private function pr_warn_deprecated(){
		if (!self::$warned_oldstyle){
			cDebug::warning("oldstyle functions are to be deprecated");
			self::$warned_oldstyle = true;
		}
	}
	
	//#####################################################################
	//# PUBLICS - OLD STYLE - to be deprecated
	//#####################################################################
	//********************************************************************************
	public function put_oldstyle($psFolder, $psFile, $poData){
		cDebug::enter();
		self::pr_warn_deprecated();
		
		$this->pr_check_realm();
		$sFullpath = $psFolder . "/" . $psFile;
		$this->put($sFullpath, $poData);
		cDebug::leave();		
	}
	
	//********************************************************************************
	public function put_array_oldstyle($psFolder, $psFile, $poData){
		cDebug::enter();
		
		self::pr_warn_deprecated();
		$sFullpath = $psFolder . "/" . $psFile;
		$aData = $this->get( $sFullpath);
		if (!$aData) $aData=[];
		$aData[] = $poData; //add to the array
		$this->put($sFullpath,$aData,true);
		cDebug::leave();		
		return $aData;
	}
	
	//********************************************************************************
	public function get_oldstyle($psFolder, $psFile){
		cDebug::enter();
		
		self::pr_warn_deprecated();
		$this->pr_check_realm();
		$oData = null;
		$sFullpath = $psFolder . "/" . $psFile;
		cDebug::extra_debug("path is $sFullpath");
		if (cObjStore::file_exists($psFolder, $psFile))
		{
			cDebug::extra_debug("migrating from cObjstore: $sFullpath");				
			$oData = cObjStore::get_file($psFolder, $psFile);
			$this->put($sFullpath, $oData);
			cObjStore::kill_file($psFolder, $psFile);
		}else{
			cDebug::extra_debug("not found in cObjstore: $sFullpath");				
			$oData = $this->get($sFullpath);
		}
		
		cDebug::leave();		
		return $oData;
	}
	
	//********************************************************************************
	public function kill_oldstyle($psfolder, $psFile){
		cDebug::enter();
		self::pr_warn_deprecated();
		$sFullpath = $psFolder . "/" . $psFile;
		cObjStore::kill($psfolder, $psFile);
		$this->kill($sFullpath);
		cDebug::leave();		
	}
	
	//#####################################################################
	//# PUBLICS
	//#####################################################################
	
	public function set_table($psTable){
		cDebug::enter();

		if ($this->table !== $psTable){
			$this->table = $psTable;
			$this->pr_create_table();
		}
		
		cDebug::leave();		
	}
	
	//********************************************************************************
	public function put($psKey, $pvAnything, $pbOverride=true){
		cDebug::enter();
		$this->pr_check_realm();
		$oSQL = self::$oSQLite;
		
		//write the compressed string to the database
		$sHash = cHash::hash($psKey);
		$oDB = self::$database;
		//cDebug::extra_debug("hash: $sHash");
		
		$sSQL = "REPLACE INTO :t (:r, :h, :c, :d ) VALUES (?, ?, ?, ?)";
		if (! $pbOverride) $sSQL = "INSERT INTO :t (:r, :h, :c, :d ) VALUES (?, ?, ?, ?)";
		$sSQL = str_replace(":t",$this->table, $sSQL);
		$sSQL = str_replace(":r",self::COL_REALM, $sSQL);
		$sSQL = str_replace(":h",self::COL_HASH, $sSQL);
		$sSQL = str_replace(":c",self::COL_CONTENT, $sSQL);
		$sSQL = str_replace(":d",self::COL_DATE, $sSQL);
		if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);
		$oStmt = $oSQL->prepare($sSQL);
		$oStmt->bindValue(1, $this->realm);
		$oStmt->bindValue(2, $sHash);
		$oStmt->bindValue(3, cGzip::encode($pvAnything));
		$oStmt->bindValue(4, date('d-m-Y H:i:s'));
		$oResultSet = $oSQL->exec_stmt($oStmt);
		
		cDebug::leave();		
	}
	
	//********************************************************************************
	public function get($psKey, $pbCheckExpiration = false){
		cDebug::enter();
		$this->pr_check_realm();
		
		//read from the database and decompress
		$sHash = cHash::hash($psKey);
		$oSQL = self::$oSQLite;
		
		$sSQL = "SELECT :r,:c,:d FROM :t where :r=? AND :h=?";
		$sSQL = str_replace(":t",$this->table, $sSQL);
		$sSQL = str_replace(":r",self::COL_REALM, $sSQL);
		$sSQL = str_replace(":h",self::COL_HASH, $sSQL);
		$sSQL = str_replace(":c",self::COL_CONTENT, $sSQL);
		$sSQL = str_replace(":d",self::COL_DATE, $sSQL);
		if ($this->SHOW_SQL) cDebug::extra_debug($sSQL);
		
		$oStmt = $oSQL->prepare($sSQL);
		$oStmt->bindValue(1, $this->realm);
		$oStmt->bindValue(2, $sHash);
		$oResultSet = $oSQL->exec_stmt($oStmt);
		if ($oResultSet == null){
			cDebug::leave();		
			return null;
		}

		$vResult = null;
		$aResult = $oResultSet->fetchArray();
		if (is_array($aResult)){	
			$sEncoded = $aResult[1];
			$vResult = cGzip::decode($sEncoded);
			
			if ($pbCheckExpiration){
				$sItemDate= $aResult[2]; //this is a string
				$dItemDate = strtotime($sItemDate); 
				$iExpires = $dItemDate + $this->expire_time;
				$iDiff = $iExpires - time();
				if ( $iDiff <= 0){
					cDebug::extra_debug("item has expired $iDiff seconds ago");
					$this->kill($psKey);
					$vResult = null;
				}else
					cDebug::extra_debug("<font style='background-color:lavender'>cached: item will expire in $iDiff seconds</font>");
			}
		}
		
		cDebug::leave();		
		return $vResult;
	}

	
	//********************************************************************************
	public function kill($psKey){
		cDebug::enter();
		$this->pr_check_realm();
		$oSQL = self::$oSQLite;
		
		//read from the database and decompress
		$sHash = cHash::hash($psKey);
		$oDB = self::$database;
		cDebug::extra_debug("hash: $sHash");
		
		$sSQL = "DELETE from :t where :r=? AND :h=?";
		$sSQL = str_replace(":t",$this->table, $sSQL);
		$sSQL = str_replace(":r",self::COL_REALM, $sSQL);
		$sSQL = str_replace(":h",self::COL_HASH, $sSQL);
		cDebug::extra_debug("SQL: $sSQL");				
		
		$oStmt = $oSQL->prepare($sSQL);
		$oStmt->bindValue(1, $this->realm);
		$oStmt->bindValue(2, $sHash);
		$oResultSet = $oSQL->exec_stmt($oStmt);
		
		cDebug::leave();		
	}
	
	
		
}
?>