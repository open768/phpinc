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

//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//%% Database 
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// for sqllite3 quickstart see https://riptutorial.com/php/example/27461/sqlite3-quickstart-tutorial
// THIS IS A WORK IN PROGRESS objstoredb is not being used
class cOBjStoreDB{
	public $rootFolder = null;
	public $realm = null;
	private static $warned_oldstyle = false;
	public static $database = null; //static as same database obj used between instances
	public static $table_exists = false;
	
	const DB_folder = "[db]";
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
		//cDebug::extra_debug("SQLLIte version:");
		//cDebug::vardump(SQLite3::version());
		$this->rootFolder = "$root/[db]";
		$this->pr_check_for_db();
		$this->pr_create_table();

		cDebug::leave();
    }
	
	//#####################################################################
	//# PRIVATES
	//#####################################################################
	private static function pr_check_for_db(){
		global $root;
		cDebug::enter();
		if (self::$database == null ){
			cDebug::warning("database not opened");
			
			//check if folder exists for database
			$sFolder = $root."/".self::DB_folder;
			if (!is_dir($sFolder)){
				cDebug::extra_debug("making folder $sFolder");
				mkdir($sFolder);
			}
			
			//now open the database
			$sPath = $sFolder."/".self::DB_FILENAME;
			if (!file_exists($sPath)) 
				cDebug::extra_debug("database file doesnt exist $sPath");				
			
			cDebug::extra_debug("opening database  $sPath");				
			$oDB = new SQLite3($sPath); 
			cDebug::extra_debug("database opened");				
			self::$database = $oDB ;
			$oDB->enableExceptions(true);
		}else
			cDebug::extra_debug("database allready open");
		cDebug::leave();
	}
	
	//********************************************************************************
	private static function pr_create_table(){
		cDebug::enter();
		
		//skip if table exists
		if (self::$database == null){
			cDebug::error("database is not open");
		}
		$oDB = self::$database;
		
		//skip if table exists
		if (self::$table_exists){
			cDebug::extra_debug("table allready checked exists");				
			cDebug::leave();
			return;
		}
		
		//check if table exists
		cDebug::extra_debug("checking table exists");				
		$sSQL = 'SELECT name FROM sqlite_master WHERE name=":t"';
		$sSQL = str_replace(":t",self::TABLE_NAME, $sSQL);
		$oResult = $oDB->query($sSQL);
		if ($oResult->fetchArray()){
			cDebug::extra_debug("table does exist");				
			cDebug::leave();
			return;
		}
		
		//table doesnt exist
		cDebug::extra_debug("table does not exist");				
		$sSQL = 'CREATE TABLE ":t" ( ":r" TEXT not null, ":h" TEXT not null, ":c" TEXT, ":d" DATETIME DEFAULT CURRENT_TIMESTAMP, primary key ( ":r", ":h"))';
		$sSQL = str_replace(":t",self::TABLE_NAME, $sSQL);
		$sSQL = str_replace(":r",self::COL_REALM, $sSQL);
		$sSQL = str_replace(":h",self::COL_HASH, $sSQL);
		$sSQL = str_replace(":c",self::COL_CONTENT, $sSQL);
		$sSQL = str_replace(":d",self::COL_DATE, $sSQL);		
		$oResult = $oDB->exec($sSQL);
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
	
	//********************************************************************************
	public function put($psKey, $pvAnything, $pbOverride=true){
		cDebug::enter();
		self::pr_check_for_db();
		$this->pr_check_realm();
		
		//write the compressed string to the database
		$sHash = cHash::hash($psKey);
		$oDB = self::$database;
		cDebug::extra_debug("hash: $sHash");
		
		$sSQL = "INSERT OR REPLACE INTO :t VALUES (?, ?, ?, ?)";
		if (! $pbOverride) $sSQL = "INSERT INTO :t VALUES (?, ?, ?, ?)";
		$sSQL = str_replace(":t",self::TABLE_NAME, $sSQL);
		$oStmt = $oDB->prepare($sSQL);
		cDebug::extra_debug("SQL: $sSQL");				
		$oStmt->bindValue(1, $this->realm);
		$oStmt->bindValue(2, $sHash);
		$oStmt->bindValue(3, cGzip::encode($pvAnything));
		$oStmt->bindValue(4, date('d-m-Y H:i:s'));
		$oResult = $oStmt->execute();
		
		cDebug::leave();		
	}
	
	//********************************************************************************
	public function get($psKey, $pbCheckExpiration = false){
		cDebug::enter();
		self::pr_check_for_db();
		$this->pr_check_realm();
		
		//read from the database and decompress
		$sHash = cHash::hash($psKey);
		$oDB = self::$database;
		cDebug::extra_debug("hash: $sHash");
		
		$sSQL = "SELECT :r,:c,:d FROM :t where :r=? AND :h=?";
		$sSQL = str_replace(":t",self::TABLE_NAME, $sSQL);
		$sSQL = str_replace(":r",self::COL_REALM, $sSQL);
		$sSQL = str_replace(":h",self::COL_HASH, $sSQL);
		$sSQL = str_replace(":c",self::COL_CONTENT, $sSQL);
		$sSQL = str_replace(":d",self::COL_DATE, $sSQL);
		cDebug::extra_debug("SQL: $sSQL");				
		
		$oStmt = $oDB->prepare($sSQL);
		$oStmt->bindValue(1, $this->realm);
		$oStmt->bindValue(2, $sHash);
		$oResultSet = $oStmt->execute();
		$aResult = $oResultSet->fetchArray();
		//cDebug::vardump($aResult);
		
		$vResult = null;
		if (is_array($aResult)){	
			$sEncoded = $aResult[1];
			cDebug::extra_debug("found Encoded: $sEncoded");				
			$vResult = cGzip::decode($sEncoded);
			//cDebug::vardump($vResult);
		}
		
		cDebug::leave();		
		return $vResult;
	}

	
	//********************************************************************************
	public function kill($psKey){
		cDebug::enter();
		self::pr_check_for_db();
		$this->pr_check_realm();
		
		//read from the database and decompress
		$sHash = cHash::hash($psKey);
		$oDB = self::$database;
		cDebug::extra_debug("hash: $sHash");
		
		$sSQL = "DELETE from :t where :r=? AND :h=?";
		$sSQL = str_replace(":t",self::TABLE_NAME, $sSQL);
		$sSQL = str_replace(":r",self::COL_REALM, $sSQL);
		$sSQL = str_replace(":h",self::COL_HASH, $sSQL);
		cDebug::extra_debug("SQL: $sSQL");				
		
		$oStmt = $oDB->prepare($sSQL);
		$oStmt->bindValue(1, $this->realm);
		$oStmt->bindValue(2, $sHash);
		$oResultSet = $oStmt->execute();
		
		cDebug::leave();		
	}
	
		
}
?>