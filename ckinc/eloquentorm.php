<?php

//ORM is eloquent: https://github.com/illuminate/database
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Schema\Builder;

class cEloquentORM {
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
}
