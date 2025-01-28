<?php
require_once cAppGlobals::$ckPhpInc . "/debug.php";

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
//
 **************************************************************************/

class cClassInstrumenter {
    static $core_classes;
    static $excluded_classes = [];          //the app should  classes here that it doesnt want to instrument
    static $instrumented_classes = [];

    //*************************************************************************
    public static function get_core_classes() {
        self::$core_classes = get_declared_classes();
    }

    static function get_class_locations() {
        $aLoaded_classes = get_declared_classes();
        $aClasses = array_diff($aLoaded_classes, self::$core_classes);
        $aClasses = array_diff($aClasses, self::$excluded_classes);
        $aClasses = array_diff($aClasses, self::$instrumented_classes);

        //remove classes from the $aClasses that  contain "composer", "se
        $aClasses = array_filter($aClasses, function ($psClass) {
            if (stristr($psClass, "composer")) return false;
            if (stristr($psClass, "secret")) return false;
            if (stristr($psClass, "facebook")) return false;
            if ($psClass == "cClassInstrumenter") return false;
            if ($psClass == "cDebug") return false;
            return true;
        });

        //work through classes building a list of filenames
        $aLocations = [];
        foreach ($aClasses as $sClass) {
            $oReflection = new ReflectionClass($sClass);
            $sFileName = $oReflection->getFileName();
            $aLocations[$sClass] = $sFileName;
        }

        //filter $aclasses, removing any item whose filename includes "/vendor"
        $aLocations = array_filter(
            $aLocations,
            function ($psFileName, $psKey) {
                if (stristr($psFileName, "\\vendor\\")) return false;
                return true;
            },
            ARRAY_FILTER_USE_BOTH
        );
        return $aLocations;
    }

    static function instrument_classes() {
        return;     //disabled

        $aLocations = self::get_class_locations();
        //cDebug::vardump($aLocations);
        //process classes

        //update instrumented classes
        $aClasses = array_keys($aLocations);
        self::$instrumented_classes = $aClasses;
    }
}
//get the core classes
cClassInstrumenter::get_core_classes();
