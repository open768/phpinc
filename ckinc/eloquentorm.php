<?php

//ORM is eloquent: https://github.com/illuminate/database
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Collection;

class cEloquentORM {
    const ELOQUENT_CLASSNAME = "Illuminate\Database\Capsule\Manager";
    const DEFAULT_DB = "default_orm.db";
    const DEFAULT_CONNECTION_NAME = "default";

    /** @var DB $capsule */
    private static $capsule;


    //**********************************************************************************************
    static function init_db() {
        //check that database class has been loaded - redundant as composer would throw an error if it wasnt
        $classname = self::ELOQUENT_CLASSNAME;
        if (!class_exists($classname))
            cDebug::error("check composer - unable to find class $classname");
        cDebug::extra_debug("found class $classname");

        //check pdo extension is loaded
        if (!extension_loaded("pdo_sqlite"))
            cDebug::error("pdo_sqlite extension is not loaded");

        //connect the ORM to a SQL lite database
        $oCapsule = new DB();
        self::$capsule = $oCapsule;
        self::add_connection(self::DEFAULT_DB, self::DEFAULT_CONNECTION_NAME);

        // Make this Capsule instance available globally via static methods... (optional)
        $oCapsule->setAsGlobal();       //should be optional but isnt
        $oCapsule->bootEloquent();

        //ok everything should be set
        cDebug::extra_debug("started eloquent");
        return $oCapsule;
    }

    //**********************************************************************************************
    /**
     * @param string $psConnectionName 
     * @return SchemaBuilder 
     */
    static function get_schema($psConnectionName) {
        $oCapsule = self::$capsule;
        $oConnection = self::get_connection($psConnectionName);
        return $oConnection->getSchemaBuilder();
    }

    //**********************************************************************************************
    //* TABLES
    //**********************************************************************************************
    static function create_table(string $psConnection, string $psTableName,  Closure $pfnCreate) {
        //cTracing::enter();
        /** @var SchemaBuilder $oSchemaBuilder */
        $oCapsule = self::$capsule;
        if (!self::is_connection_defined($psConnection)) {
            cDebug::vardump($oCapsule->getDatabaseManager()->getConnections());
            cDebug::error("no such connection :$psConnection");
        }
        $oSchema = self::get_schema($psConnection);

        cDebug::extra_debug("checking table exists  " . $psTableName);
        $bHasTable = $oSchema->hasTable($psTableName);
        if (!$bHasTable) {
            //create table
            $oSchema->create($psTableName, function ($poTable) use ($pfnCreate) {
                $pfnCreate($poTable);
            });
            cDebug::write("created table " . $psTableName);
        }
        //cTracing::leave();
    }

    static function add_relationship(Blueprint $poTable, string $psSourceCol, string $psForeignTable, string $psForeignCol) {
        $poTable->foreign($psSourceCol)->references($psForeignCol)->on($psForeignTable);
    }

    //**********************************************************************************************
    //* CONNECTIONS
    //**********************************************************************************************
    /**
     * @param string $psConnectionName 
     * @return Connection 
     */
    static function get_connection($psConnectionName) {
        $oCapsule = self::$capsule;
        return $oCapsule->getDatabaseManager()->connection($psConnectionName);
    }

    //**********************************************************************************************
    /**
     * 
     * @param string $psConnectionName 
     * @return boolean 
     */
    static function is_connection_defined($psConnectionName) {
        $oCapsule = self::$capsule;
        try {
            self::get_connection($psConnectionName);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    //**********************************************************************************************
    /**
     * 
     * @param string $psDbName 
     * @param string $psConnectionName 
     * @return void 
     */
    static function add_connection($psDbName, $psConnectionName = null) {
        // Check if the connection already exists
        //cTracing::enter();

        $sConnectionName = $psConnectionName;
        if ($sConnectionName == null) $sConnectionName = $psDbName;

        cDebug::extra_debug("checking $sConnectionName");
        $oCapsule = self::$capsule;
        if (self::is_connection_defined($sConnectionName))
            cDebug::error("connection $sConnectionName already exists");

        //create sqlite database if it does not exist
        $oDB = new cSqlLite($psDbName);
        $sDBPath = $oDB->path;
        cDebug::write("DB path is $sDBPath");


        $aConnectionParams = [
            'driver' => 'sqlite',
            'database' => $sDBPath,
            'prefix' => ''
        ];
        $oCapsule->addConnection($aConnectionParams, $sConnectionName);

        // doublecheck that the connections are there
        if (!self::is_connection_defined($sConnectionName)) {
            cDebug::error("connection was not actually added");
        }

        // Add the new connection
        cDebug::extra_debug("added new connection - name is $sConnectionName");
        //cTracing::leave();
    }

    //**********************************************************************************
    //* override the get function
    //**********************************************************************************
    /**
     * writes out the SQL if extra debugging
     * @return Collection 
     */
    static function get(QueryBuilder $poBuilder) {
        if (cDebug::is_extra_debugging()) {
            $sSQL = $poBuilder->toRawSql();
            cDebug::vardump($sSQL);
        }
        $oCollection = $poBuilder->get();
        return $oCollection;
    }

    static function pluck(QueryBuilder $poBuilder, $psColumn) {
        if (cDebug::is_extra_debugging()) {
            $sSQL = $poBuilder->toRawSql();
            cDebug::vardump($sSQL);
        }
        $oCollection = $poBuilder->pluck($psColumn);
        return $oCollection;
    }

    //**********************************************************************************
    //* transactions
    //**********************************************************************************
    static function beginTransaction($psConnectionName) {
        self::get_connection($psConnectionName)->beginTransaction();
    }

    static function commit($psConnectionName) {
        self::get_connection($psConnectionName)->commit();
    }

    static function rollBack($psConnectionName) {
        self::get_connection($psConnectionName)->rollBack();
    }
    static function vacuum($psConnectionName) {
        self::get_connection($psConnectionName)->statement('VACUUM');
    }
}
cEloquentORM::init_db();
