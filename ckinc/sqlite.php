<?php
/**************************************************************************
Copyright (C) Chicken Katsu 2021

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
// for sql errors see - 
**************************************************************************/

//#############################################################################
//#
//#############################################################################
abstract class cSQLAction{
	public $sActionType;
	public function __construct($psActionType){
		$this->sActionType = $psActionType;
	}
	public abstract function execute( SQLite3 $oDB);
}

class cSQLPrepareAction extends cSQLAction{
	private $sSQL;
	public function __construct(string $psSQL){
		parent::__construct("prepare");
		$this->sSQL = $psSQL;
	}
	public function execute(SQLite3 $oDB){
		cDebug::enter();
		$oResultset = $oDB->prepare($this->sSQL);
		cDebug::leave();
		return $oResultset;
	}
}
class cSQLQueryAction extends cSQLAction{
	private $sSQL;
	public function __construct(string $psSQL){
		parent::__construct("query");
		$this->sSQL = $psSQL;
	}
	public function execute(SQLite3 $oDB){
		cDebug::enter();
		$oResultset = $oDB->query($this->sSQL);
		cDebug::leave();
		return $oResultset;
	}
}
class cSQLExecStmtAction extends cSQLAction{
	private $oStmt;
	public function __construct($poStmt){
		parent::__construct("ExecStmt");
		$this->oStmt = $poStmt;
	}
	public function execute(SQLite3 $oDB){
		cDebug::enter();
		if ($this->oStmt == null) cDebug::error("null statement");
		$oResultset = $this->oStmt->Execute();
		cDebug::leave();
		return $oResultset;
	}
}

//#############################################################################
//#
//#############################################################################
class  cSqlLite {
	const DB_folder = "[db]";
	const RETRY_DELAY = 300;
	const BUSY_TIMEOUT = 1000;
	const NRETRIES = 4;
	const SQLITE_LOCKED = 6;
	const SQLITE_BUSY = 5;
	
	private $rootFolder = null;
	public $dbname = null;
	public $path = null;
	public $database = null;		//always the same database
	
	//#####################################################################
	//# constructor
	//#####################################################################
	function __construct(string $psDB) {
		global $root;
		cDebug::enter();

		//cDebug::extra_debug("SQLLIte version:");
		//cDebug::vardump(SQLite3::version());
		$this->db_filename = $psDB;
		$this->rootFolder = "$root/[db]";
		$this->pr_check_for_db($psDB);
		
		cDebug::leave();
	}

	//#####################################################################
	//# PRIVATES
	//#####################################################################
	private function pr_check_for_db(string $psDB){
		global $root;
		//cDebug::enter();
		if ($this->database == null ){
			cDebug::warning("database not opened");
			
			//check if folder exists for database
			$sFolder = $root."/".self::DB_folder;
			if (!is_dir($sFolder)){
				cDebug::extra_debug("making folder $sFolder");
				mkdir($sFolder);
			}
			
			//now open the database
			$sPath = $sFolder."/".$psDB;
			if (!file_exists($sPath)) 
				cDebug::extra_debug("database file doesnt exist $sPath");				
			
			cDebug::extra_debug("opening database  $sPath");				
			$oDB = new SQLite3($sPath); 
			cDebug::extra_debug("database opened");				
			$this->database = $oDB ;
			$oDB->enableExceptions(true);
			$oDB->busyTimeout(self::BUSY_TIMEOUT);
		}

		//cDebug::leave();
	}
	
	//********************************************************************************
	//removed repeated code to handle SQL busy or locked
	private function pr_do_action( cSQLAction $poAction){
		$bRetryAction = true;
		$iRetryCount=0;
		$oResultSet=null;
		cDebug::enter();
		
		$oDB = $this->database;
		while($bRetryAction){
			$iErr = 0;
			try{
				$oResultSet = $poAction->execute($oDB);
				if ($oResultSet == null) {
					$iErr = $oDB->lastErrorCode();
				}else
					$bRetryAction=false;
			}catch(Exception $e){
				$iErr = $oDB->lastErrorCode();
			}
			
			switch($iErr){
				case 0:
					break;
				case self::SQLITE_LOCKED:
				case self::SQLITE_BUSY:
					if ($iRetryCount< self::NRETRIES){
						$iRetryCount ++;
						$bRetryAction=true;
						usleep(self::RETRY_DELAY);
					}else
						throw new Exception("Database locked - $poAction->sActionType given up after 3 tries");
					break;
				default:
					throw new Exception ("SQL Error : $iErr");
			}
		}
		
		cDebug::leave();		
		return $oResultSet;
	}
	

	//#####################################################################
	//# PUBLICS
	//#####################################################################
	public function get_db_path($psDBname){
		global $root;
		return   $root."/".self::$DB_FOLDER."/".psDBname;
	}
	
	//********************************************************************************
	public function create_db($psDBname){
		$dbpath = $self->get_db_path($psDBname);
	}
	
	//********************************************************************************
	public function open_db( $psDBname){
		$dbpath = $self->get_db_path($psDBname);
	}
	
	//********************************************************************************
	public function prepare($psSQL){
		cDebug::enter();
		$oAction = new cSQLPrepareAction($psSQL);
		$oResultSet = $this->pr_do_action($oAction);
		cDebug::leave();	
		return $oResultSet;
	}
	//********************************************************************************
	public function query($psSQL){
		cDebug::enter();
		$oAction = new cSQLQueryAction($psSQL);
		$oResultSet = $this->pr_do_action($oAction);
		cDebug::leave();	
		return $oResultSet;
	}
	
	//********************************************************************************
	public function exec_stmt($poStmt){
		cDebug::enter();
		$oAction = new cSQLExecStmtAction($poStmt);
		$oResultSet = $this->pr_do_action($oAction);
		cDebug::leave();		
		return $oResultSet;
	}
	
}
?>