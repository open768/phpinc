<?php
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//% OBJSTOREDB - simplistic store objects without a database!
//%
//% solves Problem -  thousands files on busy websites that exceed inode quotas.
//%
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

require_once("$phpinc/ckinc/gz.php");
require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/ckinc/hash.php");

//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//%% Database 
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// for sqllite3 quickstart see https://riptutorial.com/php/example/27461/sqlite3-quickstart-tutorial
// THIS IS A WORK IN PROGRESS objstoredb is not being used
class cOBjStoreDB{
	public $rootFolder = null;
	public $realm = null;
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
		}
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
		$oObj = new cOBjStoreDB();
		$oObj->realm = self::OBJSTORE_REALM;
		$oObj->put( self::OBJSTORE_CREATE_KEY,  date('d-m-Y H:i:s'));
		
		cDebug::leave();
	}
	
	//#####################################################################
	//# PUBLICS
	//#####################################################################
	
	//********************************************************************************
	public function put($psKey, $psValue, $pbOverride=true){
		cDebug::enter();
		self::pr_check_for_db();
		if ($this->realm == null) cDebug::error("realm is null");
		
		$oDB = self::$database;
		
		$sSQL = "INSERT OR REPLACE INTO :t VALUES (:r, :h, :c, :d)";
		if (! $pbOverride) $sSQL = "INSERT INTO :t VALUES (:r, :h, :c, :d)";
		$sSQL = str_replace(":t",self::TABLE_NAME, $sSQL);
		$oStmt = $oDB->prepare($sSQL);
		$oStmt->bindValue(":r", $this->realm);
		$oStmt->bindValue(":h", cHash::hash($psKey));
		$oStmt->bindValue(":c", $psValue);
		$oStmt->bindValue(":d", date('d-m-Y H:i:s'));
		$oResult = $oStmt->execute();
		
		cDebug::leave();		
	}
	
		
}
?>