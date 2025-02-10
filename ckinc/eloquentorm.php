<?php

//ORM is eloquent: https://github.com/illuminate/database
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class cEloquentORM {
    const ELOQUENT_CLASSNAME = "Illuminate\Database\Capsule\Manager";
    const DEFAULT_DB = "default_orm.db";
    const DEFAULT_CONNECTION_NAME = "default";

    private static $capsule;

    static function create_table(string $psTableName, Closure $pfnCreate) {
        /** @var CapsuleManager $oManager */
        $oManager = self::$capsule;

        /** @var oSchemaBuilder $oSchemaBuilder */
        $oSchemaBuilder = $oManager->schema();

        cDebug::extra_debug("checking table exists  " . $psTableName);
        $bHasTable = $oSchemaBuilder->hasTable($psTableName);
        if (!$bHasTable) {
            //create table
            $oSchemaBuilder->create($psTableName, function ($poTable) use ($pfnCreate) {
                $pfnCreate($poTable);
            });
            cDebug::extra_debug("created table " . $psTableName);
        }
    }

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
    static function add_connection($psDbName, $psConnectionName = null) {
        // Check if the connection already exists
        $oCapsule = self::$capsule;
        if (isset($oCapsule->getDatabaseManager()->getConnections()[$psDbName]))
            cDebug::error("connection $psDbName already exists");

        //create sqlite database if it does not exist
        $oDB = new cSqlLite($psDbName);
        $sDBPath = $oDB->path;
        cDebug::write("DB path is $sDBPath");

        $sConnectionName = $psConnectionName;
        if ($sConnectionName == null) $sConnectionName = $psDbName;

        $oCapsule->addConnection([
            'driver' => 'sqlite',
            'database' => $sDBPath,
            'prefix' => ''
        ], $sConnectionName);

        // Add the new connection
        cDebug::extra_debug("added new connection - name is $psDbName");
    }
}
cEloquentORM::init_db();

//*************************************************************************************************
//  following code from 
//      https://stackoverflow.com/questions/36764838/how-to-use-transaction-in-eloquent-model
//
class TransactionsORM extends EloquentModel {
    public $connection_name = null;

    public function __construct($psConnectionName) {
        $this->connection_name = $psConnectionName;
    }
    public function get_connection() {
        return self::getConnectionResolver($this->connection_name)->connection();
    }

    public function beginTransaction() {
        $this->get_connection()->beginTransaction();
    }

    public function commit() {
        $this->get_connection()->commit();
    }

    public function rollBack() {
        $this->get_connection()->rollBack();
    }
    public function vacuum() {
        $this->get_connection()->statement('VACUUM');
    }
}
