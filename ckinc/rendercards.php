<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

//#######################################################################
//#######################################################################
/** 
 * Class cRenderCards - uses MATERIAL DESIGN
 */
class cRenderCards
{
    private static $iCardID = 0;
    private static $iChipID = 0;

    //**************************************************************************
    //*
    //**************************************************************************
    public static function card_start($psTitle = "", $psExtraClass = "")
    {
        self::$iCardID++;
        $sID = "CARDID_" . self::$iCardID;
        $sClass = "mdl-card mdl-shadow--2dp rapport-card";

        if ($psExtraClass !== "") $sClass .= " $psExtraClass";

        echo  "<div class='$sClass' id='$sID'>";
        if ($psTitle !== "")
            self::card_title("<h3 class='card_title'>$psTitle</h3>");

        return self::$iCardID;
    }
    //**************************************************************************
    public static function card_title($psTitle)
    {
        echo "<div class='mdl-card__title'>$psTitle</div>";
    }

    //**************************************************************************
    public static function action_start()
    {
        echo "<div class='mdl-card__actions mdl-card--border'>";
    }

    //**************************************************************************
    public static function body_start()
    {
        echo "<div class='mdl-card__supporting-text'>";
    }

    //**************************************************************************
    //*
    //**************************************************************************
    public static function action_end()
    {
        echo "</div><!-- mdl actions -->";
        cDebug::flush();
    }

    //**************************************************************************
    public static function body_end()
    {
        echo "</div><!-- mdl body -->";
        cDebug::flush();
    }

    //**************************************************************************
    public static function card_end()
    {
        echo "</div><!--mdl-card-->";
        cDebug::flush();
    }

    //**************************************************************************
    //*
    //**************************************************************************
    public static function chip($psContent, $psTooltip = null)
    {
        self::$iChipID++;
        $sID = "CHIPID_" . self::$iChipID;
        echo "<span class='mdl-chip'>";
        echo    "<span class='mdl-chip__text' id='$sID'>$psContent</span>";
        echo "</span>";

        if ($psTooltip)
            echo "<div class='mdl-tooltip' data-mdl-for='$sID'>$psTooltip</div>";
        return self::$iChipID;
    }
}
