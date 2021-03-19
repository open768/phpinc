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
	public static $database = null; //static as same database obj used between instances
	public static $table_exists = false;
	
	const DB_folder = "[db]";
	const DB_FILENAME = "objstore.db";
	const TABLE_NAME = "objstore";
	const COL_HASH = "HA";
	const COL_CONTENT = "CO";
	const COL_DATE = "DA";
	
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
		$db = self::$database;
		
		//skip if table exists
		if (self::$table_exists){
			cDebug::extra_debug("table allready checked exists");				
			cDebug::leave();
			return;
		}
		
		//check if table exists
		cDebug::extra_debug("checking table exists");				
		$stmt = $db->prepare('SELECT name FROM sqlite_master WHERE name=":t"');
		$stmt->bindValue(':t', self::TABLE_NAME);
		$result = $stmt->execute();
		if ($result->fetchArray()){
			cDebug::extra_debug("table does exist");				
			cDebug::leave();
			return;
		}
		
		//table doesnt exist
		cDebug::extra_debug("table does not exist");				
		$stmt = $db->prepare('CREATE TABLE ":t" ( ":h" TEXT, ":c" TEXT, ":d" DATETIME DEFAULT CURRENT_TIMESTAMP)');
		$stmt->bindValue(':t', self::TABLE_NAME);
		$stmt->bindValue(':h', self::COL_HASH);
		$stmt->bindValue(':c', self::COL_CONTENT);
		$stmt->bindValue(':d', self::COL_DATE);
		$result = $stmt->execute();
		cDebug::extra_debug("table created");				
		
		cDebug::leave();
	}
	
	//#####################################################################
	//# PUBLICS
	//#####################################################################
	public static function open_db(){
		//get the database object
		$bCreate = false;
		
		cDebug::enter();
		//--------------------------------------------------
		
		if (self::$database !== null)
		{	
			cDebug::extra_debug("database allready open");
			cDebug::leave();
			return self::$database;
		}
		
		$sFilename = self::$rootFolder."/".self::DB_FILENAME;
		cDebug::extra_debug("opening SQL database $sFilename");
		//if the file doesnt exist create the database
		$bCreate = !file_exists($sFilename);
		$oDB = new SQLlite3($sFilename);
		self::$database = $oDB;
		if ($bCreate) 
			self::pr_create_table();
		
		//--------------------------------------------------
		cDebug::leave();
		return $oDB;
	}
	
	//********************************************************************************
	public static function put($psFolder, $psFile, $psContent){
		cDebug::enter();
		self::pr_check_for_db();
		
		$sSQL = 
			'INSERT INTO '.
				'"'.self::TABLE_NAME.'"'.
			'" ("'.self::COL_FOLDER.'", "'.self::COL_FILE.'", "'.self::COL_CONTENT.'")'.
				' VALUES (?, ?, ?)';
		cDebug::extra_debug("SQL is $sSQL");
		$oStmt = self::$database->prepare($sSQL);
		$oStmt->bindValue(1,$psFolder);
		$oStmt->bindValue(2,$psfile);
		$oStmt->bindValue(3,$psContent);
		$oStmt->execute();
		$oStmt->finalize();
		
		cDebug::leave();		
	}
	
	//********************************************************************************
	public static function get($psFolder, $psFile){
		cDebug::enter();
		self::pr_check_for_db();
		
		$sSQL = 
			'SELECT '.
				'"'.self::COL_CONTENT.'"'.
			' FROM "'.self::TABLE_NAME.'"'.
			' WHERE '.
				' "'.self::COL_FOLDER.'"=? AND "'.self::COL_FILE.'"=?';
				
		cDebug::extra_debug("SQL is $sSQL");
		$oStmt = self::$database->prepare($sSQL);
		$oStmt->bindValue(1,$psFolder);
		$oStmt->bindValue(2,$psfile);
		$oResult = $oStmt->execute();
		$aData = $oResult->fetchArray(SQLITE3_NUM);
		$oStmt->finalize();
		
		cDebug::leave();
		return $aData[0];
	}
}
?>