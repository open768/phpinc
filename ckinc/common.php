<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

require_once  "$phpInc/ckinc/debug.php";
require_once  "$phpInc/ckinc/header.php";

class cCommon
{
    public static $SHOW_PROGRESS = TRUE;
    const SECONDS_IN_MONTH = 31 * 24 * 60 * 60;
    const ENGLISH_DATE_FORMAT = "d/m/Y H:i";
    const EXCEL_DATE_FORMAT = "Y-m-d H:i:s";
    const RGRAPH_DATE_FORMAT = "Y-m-d\TH:i:s";
    const UTC_DATE_FORMAT = "Y-m-d\TH:i:s\Z";
    const PHP_UK_DATE_FORMAT = "d-m-Y H:i";
    const PHP_SHORT_UK_DATE_FORMAT = "d-m-Y";
    const MINS_IN_HOUR = 60;
    const HOUR_IN_DAY = 24;
    const MINS_IN_DAY = self::HOUR_IN_DAY * self::MINS_IN_HOUR;
    const DAY_IN_WEEK = 7;
    const MINS_IN_WEEK = self::MINS_IN_DAY * self::DAY_IN_WEEK;
    const MINS_IN_MONTH = self::MINS_IN_DAY * 31;
    const PROGRESS_CHAR = "&#8667;";

    //**************************************************************************
    public static function filename()
    {
        global $_SERVER;
        return basename($_SERVER["PHP_SELF"]);
    }

    //**************************************************************************
    public static function write_json($poThing)
    {
        if (cDebug::is_debugging()) {
            cDebug::write("json output:");
            cDebug::vardump($poThing, true);
        } else
            echo json_encode($poThing);
    }

    //**************************************************************************
    public static function serialise($poThing)
    {
        if (!is_object($poThing)) cDebug::error("not an object");
        return get_object_vars($poThing);
    }


    //**************************************************************************
    //code for following function based on http://php.net/manual/en/function.rmdir.php
    public static function delTree($psDir)
    {
        $aFiles = array_diff(scandir($psDir), array('.', '..'));
        foreach ($aFiles as $sfile) {
            try {
                if (is_dir("$psDir/$sfile") && !is_link($psDir))
                    self::delTree("$psDir/$sfile");
                else
                    unlink("$psDir/$sfile");
            } catch (Exception $e) {
            }
        }
        cDebug::write("removing $psDir");
        rmdir($psDir);
    }

    //**************************************************************************
    public static function flushprint($psWhat = self::PROGRESS_CHAR)
    {

        if (self::$SHOW_PROGRESS) {
            print $psWhat;
            cDebug::flush();
        }
    }

    //**************************************************************************
    public static function do_echo($psWhat)
    {
        print "$psWhat\n";
        if (cDebug::is_debugging()) print "<br>";
    }

    //**************************************************************************
    public static function get_session($psKey)
    {
        global $_SESSION;

        if (isset($_SESSION[$psKey]))
            return $_SESSION[$psKey];
        else
            return "";
    }

    //**************************************************************************
    public static function is_numeric_name($psName)
    {
        return (preg_match('/\/\d+/', $psName, $matches) || preg_match('/\-\d+/', $psName, $matches));
    }

    //**************************************************************************
    public static function is_string_set($psValue)
    {
        $bResult = (($psValue != null) && ($psValue !== ""));
        //if (!$bResult) cDebug::write("string: $psValue result: not set");
        return $bResult;
    }

    //**************************************************************************
    public static function reformat_date($psDate, $psOldFormat, $psNewFormat)
    {
        $oDate = DateTime::createFromFormat($psOldFormat, $psDate);
        return $oDate->format($psNewFormat);
    }

    //**************************************************************************
    public static function strip_non_printing($psIn)
    {
        return preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', $psIn);
    }

    //**************************************************************************
    public static function get_first_alphabet_char($psInput)
    {
        if (preg_match('/[a-z]/i', $psInput, $match))
            return  $match[0];
        else
            return null;
    }

    //**************************************************************************
    public static function my_IP_address()
    {
        return GetHostByName(null);
    }

    //**************************************************************************
    public static function save_to_session($psKey, $psValue)
    {
        global $_SESSION;
        cDebug::extra_debug("saving: $psKey =&gt; $psValue");
        $_SESSION[$psKey] = $psValue;
        $check = $_SESSION[$psKey];
        if ($check !== $psValue)
            cDebug::error("unable to save to session : $psKey =&gt; $psValue");
    }
}

//###################################################################################
//#
//###################################################################################
class cPageOutput
{
    //**************************************************************************
    public static function warning($psText)
    {
        cDebug::write("<b><font size='+2'color='brick'>Warning:</font></b> $psText");
    }

    //**************************************************************************
    public static function put_in_wbrs($psInput, $piInterval = 20)
    {
        if (substr_count($psInput, " ") > 0)
            return $psInput;
        else {
            $aSplit = str_split($psInput, $piInterval);
            $sJoined = implode("<wbr>", $aSplit);
            return $sJoined;
        }
    }
    //**************************************************************************
    public static function fixed_width_div($piWidth, $psContent)
    {
        $sBroken = self::put_in_wbrs($psContent);
        echo "<div style='width:{$piWidth}px;max-width:{$piWidth}px;'>{$sBroken}</div>";
    }

    //**************************************************************************
    public static function write_JS_class_constant_IDs($psClassName)
    {
        $oReflection = new ReflectionClass($psClassName);
        $aConsts = $oReflection->getConstants();
        if (count($aConsts) == 0) return;
        $aStatics = $oReflection->getStaticProperties();

        echo "<script>\n"; {
            echo "class {$psClassName} {\n";
            foreach ($aConsts as $sKey => $sValue)
                echo "\tstatic {$sKey} = '{$sValue}'\n";
            foreach ($aStatics as $sKey => $sValue)
                echo "\tstatic {$sKey} = '{$sValue}'\n";
            echo "}";
        }
        echo "</script>\n";
    }

    //**************************************************************************
    public static function errorbox($psMessage)
    {
?>
        <p>
        <div class='w3-panel w3-red w3-leftbar'>
            <h2>Oops there was an error</h2>
            <p>
                <?= $psMessage ?>
        </div>
    <?php
        cDebug::flush();
    }

    //**************************************************************************
    public static function div_with_cols($piCols, $psExtra = "")
    {
        echo "<div style='column-count:$piCols;overflow-wrap:break-word' $psExtra>";
    }

    //**************************************************************************
    public static function messagebox($psMessage)
    {
    ?>
        <p>
        <div class='w3-panel w3-blue w3-round-large w3-padding-16 w3-leftbar'>
            <?= $psMessage ?>
        </div>
<?php
        cDebug::flush();
    }
}
?>