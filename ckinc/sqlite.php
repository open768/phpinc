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
const SQLITE_LOCKED = 6;
const SQLITE_BUSY = 5;

class  cSqlLite {
	const DB_folder = "[db]";
	const SQL_RETRY_DELAY = 50;
	
	private $rootFolder = null;
	public $dbname = null;
	public $path = null;
	public $database = null;		//always the same database
	
	//#####################################################################
	//# constructor
	//#####################################################################
	function __construct($psDB) {
		global $root;
		//cDebug::extra_debug("SQLLIte version:");
		//cDebug::vardump(SQLite3::version());
		$this->db_filename = $psDB;
		$this->rootFolder = "$root/[db]";
		$this->pr_check_for_db($psDB);
	}

	//#####################################################################
	//# PRIVATES
	//#####################################################################
	private function pr_check_for_db($psDB){
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
		}

		//cDebug::leave();
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
		$bRetryOnLock = true;
		$iRetryCount=0;
		$oResult=null;
		cDebug::enter();
		
		if ($psSQL == null) cDebug::error("null SQL");
		if (!strpos($psSQL,"?")) cDebug::error("SQL unsuitable for prepare: $psSQL");
		
		$oDB = $this->database;
		cDebug::extra_debug("SQL: $psSQL");
		while($bRetryOnLock){
			try{
				$oResult = $oDB->prepare($psSQL);
				$bRetryOnLock=false;
			}catch(Exception $e){
				$iErr = $oDB->lastErrorCode();
				
				switch($iErr){
					case SQLITE_LOCKED:
					case SQLITE_BUSY:
						if ($bRetryOnLock && $iRetryCount<3){
							$iRetryCount ++;
							$bRetryOnLock=false;
							usleep(self::SQL_RETRY_DELAY);
						}else
							throw new Exception("Database locked - prepare given up after 3 tries");
						break;
					default:
						throw new Exception ("SQL Error : $iErr");
				}
			}
		}
		
		if ($oResult == null) cDebug::error("null statement for: $psSQL");
		cDebug::leave();		
		return $oResult;
	}
	//********************************************************************************
	public function query($psSQL){
		$bRetryOnLock = true;
		$iRetryCount=0;
		$oResult=null;
		cDebug::enter();
		
		if ($psSQL == null) cDebug::error("null SQL");
		
		$oDB = $this->database;
		cDebug::extra_debug("SQL: $psSQL");
		while($bRetryOnLock){
			try{
				$oResult = $oDB->query($psSQL);
				$bRetryOnLock=false;
			}catch(Exception $e){
				$iErr = $oDB->lastErrorCode();
				
				switch($iErr){
					case SQLITE_LOCKED:
					case SQLITE_BUSY:
						if ($bRetryOnLock && $iRetryCount<3){
							$iRetryCount ++;
							$bRetryOnLock=false;
							usleep(self::SQL_RETRY_DELAY);
						}else
							throw new Exception("Database locked - prepare given up after 3 tries");
						break;
					default:
						throw new Exception ("SQL Error : $iErr");
				}
			}
		}
		cDebug::leave();		
		return $oResult;
	}
	
	//********************************************************************************
	public function exec_stmt($poStmt){
		$bRetryOnLock = true;
		$iRetryCount=0;
		$oResult=null;
		cDebug::enter();
		if ($poStmt == null) cDebug::error("null statement");
		
		$oDB = $this->database;
		while($bRetryOnLock){
			try{
				$oResult = $poStmt->execute();
				$bRetryOnLock=false;
			}catch(Exception $e){
				$iErr = $oDB->lastErrorCode();
				
				switch($iErr){
					case SQLITE_LOCKED:
					case SQLITE_BUSY:
						if ($bRetryOnLock && $iRetryCount<3){
							$iRetryCount ++;
							$bRetryOnLock=false;
							usleep(self::SQL_RETRY_DELAY);
						}else
							throw new Exception("Database locked - prepare given up after 3 tries");
						break;
					default:
						throw new Exception ("SQL Error : $iErr");
				}
			}
		}
		
		cDebug::leave();		
		return $oResult;
	}
	
}
?>