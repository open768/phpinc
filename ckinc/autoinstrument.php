<?php
require_once cAppGlobals::$ckPhpInc . "/debug.php";

//##############################################################################
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
        $aLocations = self::get_class_locations();
        cDebug::vardump($aLocations);
        //process classes

        //update instrumented classes
        $aClasses = array_keys($aLocations);
        self::$instrumented_classes = $aClasses;
    }
}
//get the core classes
cClassInstrumenter::get_core_classes();