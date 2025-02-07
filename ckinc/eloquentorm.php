<?php

//ORM is eloquent: https://github.com/illuminate/database
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class cEloquentORM {
    const ELOQUENT_CLASSNAME = "Illuminate\Database\Capsule\Manager";

    static function create_table(string $psTableName, Closure $pfnCreate) {
        /** @var CapsuleManager $oManager */
        $oManager = cMissionManifest::$capsuleManager;
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
    static function init_db($psDbName) {
        //check that database class has been loaded - redundant as composer would throw an error if it wasnt
        $classname = self::ELOQUENT_CLASSNAME;
        if (!class_exists($classname))
            cDebug::error("check composer - unable to find class $classname");
        cDebug::extra_debug("found class $classname");

        //check pdo extension is loaded
        if (!extension_loaded("pdo_sqlite"))
            cDebug::error("pdo_sqlite extension is not loaded");

        //create sqlite database if it does not exist
        $oDB = new cSqlLite($psDbName);
        $sDBPath = $oDB->path;
        cDebug::write("DB path is $sDBPath");

        //connect the ORM to a SQL lite database
        $oCapsule = new DB();
        $oCapsule->addConnection([
            'driver' => 'sqlite',
            'database' => $sDBPath,
            'prefix' => ''
        ]);

        // Make this Capsule instance available globally via static methods... (optional)
        $oCapsule->setAsGlobal();       //should be optional but isnt
        $oCapsule->bootEloquent();

        //ok everything should be set
        cDebug::extra_debug("started eloquent - db is $sDBPath");
        return $oCapsule;
    }
}

//*************************************************************************************************
//  following code from 
//      https://stackoverflow.com/questions/36764838/how-to-use-transaction-in-eloquent-model
//
class TransactionsORM extends EloquentModel {
    public static function get_connection() {
        return self::getConnectionResolver()->connection();
    }

    public static function beginTransaction() {
        self::get_connection()->beginTransaction();
    }

    public static function commit() {
        self::get_connection()->commit();
    }

    public static function rollBack() {
        self::get_connection()->rollBack();
    }
    public static function vacuum() {
        self::get_connection()->statement('VACUUM');
    }
}
