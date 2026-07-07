<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024
This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

class cAppStatus {
    const DATABASE_DOWN = false;
    const SITE_DOWN_FILENAME = ".duck";
    static $site_down = false;

    public static function get_sitedown_fname() {
        return cCommon::add_filename_to_dir(cAppGlobals::$root, self::SITE_DOWN_FILENAME);
    }

    public static function site_down() {
        $sfilename = self::get_sitedown_fname();
        touch($sfilename);
        self::$site_down = true;
    }

    public static function site_up() {
        $sfilename = self::get_sitedown_fname();
        @unlink($sfilename);
        self::$site_down = false;
    }

    public static function check_site_down() {
        $sfilename = self::get_sitedown_fname();
        self::$site_down = file_exists($sfilename);
    }
}
