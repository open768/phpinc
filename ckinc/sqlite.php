<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024
This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

//#############################################################################
//#
//#############################################################################
abstract class cSQLAction {
    public $sActionType;
    public function __construct($psActionType) {
        $this->sActionType = $psActionType;
    }
    public abstract function execute(SQLite3 $oDB);
}

//********************************************************************************
class cSQLPrepareAction extends cSQLAction {
    private string $sSQL;

    public function __construct(string $psSQL) {
        parent::__construct("prepare");
        $this->sSQL = $psSQL;
    }

    /**
     * runs the prepare action
     * 
     * @param SQLite3 $oDB 
     * @return SQLite3Stmt|false 
     */
    public function execute(SQLite3 $oDB) {
        /** @var SQLite3Stmt $oResultset */
        $oResultset = $oDB->prepare($this->sSQL);
        return $oResultset;
    }
}

//********************************************************************************
class cSQLQueryAction extends cSQLAction {
    private $sSQL;
    public function __construct(string $psSQL) {
        parent::__construct("query");
        $this->sSQL = $psSQL;
    }
    public function execute(SQLite3 $poSqlDb) {
        //cDebug::extra_debug("SQL: {$this->sSQL}");
        $oResultset = $poSqlDb->query($this->sSQL);
        return $oResultset;
    }
}

//********************************************************************************
class cSQLExecStmtAction extends cSQLAction {
    /** @var SQLite3Stmt $oStmt*/
    private $oStmt;

    public function __construct(SQLite3Stmt $poStmt) {
        parent::__construct("ExecStmt");
        $this->oStmt = $poStmt;
    }
    public function execute(SQLite3 $oDB) {
        $oStmt = $this->oStmt;
        if ($oStmt === null) cDebug::error("null statement");

        $oResultset = $oStmt->Execute();
        return $oResultset;
    }
}
//#############################################################################
//# utility functions
//#############################################################################
class cSqlLiteUtils {
    //********************************************************************************
    static function fetch_all(SQLite3Result $poResultset) {
        $aRows = [];
        while ($oRow = $poResultset->fetchArray(SQLITE3_ASSOC))
            $aRows[] = (object) $oRow;
        return $aRows;
    }

    //********************************************************************************
    static function vacuum(string $psDatabase) {
        $oDB = cSqlLite::open_sql_db($psDatabase);
        $oDB->exec("VACUUM;");
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
    /** @var SQLite3 $database */
    public $database = null;        //always the same database
    static $in_transaction = false;

    //#####################################################################
    //# constructor
    //#####################################################################
    function __construct(string $psDB) {

        //cDebug::enter();

        //cDebug::extra_debug("SQLLIte version:");
        //cDebug::vardump(SQLite3::version());
        $this->dbname = $psDB;
        $this->rootFolder = cAppGlobals::$root . "/[db]";
        $this->pr_check_for_db($psDB);

        //cDebug::leave();
    }

    //#####################################################################
    //# PRIVATES
    //#####################################################################
    static function open_sql_db(string $psDBFilename) {

        //cDebug::extra_debug("opening database");

        //check if folder exists for database
        $sFolder = cAppGlobals::$root . "/" . self::DB_folder;
        if (!is_dir($sFolder)) {
            cDebug::extra_debug("making folder $sFolder");
            mkdir($sFolder);
        }

        //check if db_exists
        if (!self::db_exists($psDBFilename))
            cDebug::extra_debug("database file doesnt exist $psDBFilename");

        //now open the database
        $sPath = $sFolder . "/" . $psDBFilename;
        cDebug::extra_debug("opening database  $sPath");
        $oDB = new SQLite3($sPath);
        //cDebug::extra_debug("database opened");

        return $oDB;
    }

    //********************************************************************************
    static function db_exists(string $psDBFilename) {
        $sFolder = cAppGlobals::$root . "/" . self::DB_folder;
        $sPath = $sFolder . "/" . $psDBFilename;
        return file_exists($sPath);
    }

    //********************************************************************************
    private function pr_check_for_db(string $psDBFilename) {

        //cDebug::enter();
        if ($this->database == null) {
            $oDB = self::open_sql_db($psDBFilename);
            $this->database = $oDB;
            $oDB->enableExceptions(true);
            $oDB->busyTimeout(self::BUSY_TIMEOUT);
        }

        //cDebug::leave();
    }

    //********************************************************************************
    /**
     * general purpose SQL execution - handles retries and errors
     * 
     * @param cSQLAction $poAction 
     * @return mixed 
     */
    private function pr_do_action(cSQLAction $poAction) {
        $bRetryAction = true;
        $iRetryCount = 0;
        $oResultSet = null;
        //cDebug::enter();

        $oDB = $this->database;
        while ($bRetryAction) {
            $iErr = 0;
            $sErr = null;
            try {
                $oResultSet = $poAction->execute($oDB);
                if ($oResultSet == null) {
                    $iErr = $oDB->lastErrorCode();
                } else
                    $bRetryAction = false;
            } catch (Exception $e) {
                $iErr = $oDB->lastErrorCode();
                $sErr = $oDB->lastErrorMsg();
            }

            switch ($iErr) {
                case 0:
                    break;
                case self::SQLITE_LOCKED:
                case self::SQLITE_BUSY:
                    if ($iRetryCount < self::NRETRIES) {
                        $iRetryCount++;
                        $bRetryAction = true;
                        usleep(self::RETRY_DELAY);
                    } else
                        cDebug::error("Database locked - $poAction->sActionType given up after " . self::NRETRIES . "tries");
                    break;
                default:
                    cDebug::error("SQL Error : code=$iErr, msg=$sErr");
            }
        }

        //cDebug::leave();		
        return $oResultSet;
    }


    //#####################################################################
    //# PUBLICS
    //#####################################################################
    public function get_db_path($psDBname) {

        return  cAppGlobals::$root . "/" . self::DB_folder . "/" . $psDBname;
    }

    //********************************************************************************
    public function create_db($psDBname) {
        $dbpath = $this->get_db_path($psDBname);
    }

    //********************************************************************************
    public function open_db($psDBname) {
        $dbpath = $this->get_db_path($psDBname);
    }

    //********************************************************************************
    /**
     * prepares a SQL statement
     * 
     * @param mixed $psSQL 
     * @return SQLite3Stmt  
     */
    public function prepare($psSQL) {
        //cDebug::enter();
        $oAction = new cSQLPrepareAction($psSQL);
        /** @var SQLite3Stmt $oResultSet */
        $oResultSet = $this->pr_do_action($oAction);
        //cDebug::leave();	
        return $oResultSet;
    }
    //********************************************************************************
    public function querySQL(string $psSQL) {
        //cDebug::enter();
        $oAction = new cSQLQueryAction($psSQL);
        $oResultSet = $this->pr_do_action($oAction);
        //cDebug::leave();	
        return $oResultSet;
    }

    //********************************************************************************
    /**
     * executes a statement
     * 
     * @param mixed $poStmt 
     * @return SQLite3Result 
     * @throws Exception 
     */
    public function exec_stmt(SQLite3Stmt $poStmt) {
        //cDebug::enter();
        cDebug::extra_debug($poStmt->getSQL(TRUE));

        $oAction = new cSQLExecStmtAction($poStmt);
        $oResultSet = $this->pr_do_action($oAction);
        //cDebug::leave();		
        return $oResultSet;
    }

    //********************************************************************************
    public function table_exists(string $psName) {
        $sSQL = 'SELECT name FROM sqlite_master WHERE name=:table';
        $oStmt = $this->prepare($sSQL);
        $oStmt->bindValue(":table", $psName);
        $oResultSet = $this->exec_stmt($oStmt);
        $aResults = $oResultSet->fetchArray(SQLITE3_ASSOC);
        //cDebug::vardump($aResults);
        if (is_array($aResults))
            $bExists = count($aResults) > 0;
        else
            $bExists = false;

        return $bExists;
    }


    //********************************************************************************
    //* transactions
    //********************************************************************************
    public function begin_transaction() {
        if (self::$in_transaction) cDebug::error("allready in a transaction");
        self::$in_transaction = true;
        $oDB = $this->database;
        $oDB->exec("BEGIN;");
    }

    //********************************************************************************
    public function commit() {
        if (!self::$in_transaction) cDebug::error("not allready in a transaction");
        self::$in_transaction = false;
        $oDB = $this->database;
        $oDB->exec("COMMIT;");
    }

    public function rollback() {
        if (!self::$in_transaction) cDebug::error("not allready in a transaction");
        self::$in_transaction = false;
        $oDB = $this->database;
        $oDB->exec("ROLLBACK;");
    }
}
