<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/
require_once  cAppGlobals::$ckPhpInc . "/session.php";
require_once  cAppGlobals::$ckPhpInc . "/common.php";

class DebugException extends Exception {
}

class cTracing {
    static $ENTER_DEPTH = 0;
    const CSS_CLASS_NAME = "tracing_button_class";
    private static $b_output_js = false;

    public static function enter($psOverrideName = null, $pbOnce = false) {
        if (cDebug::is_extra_debugging() || $pbOnce) {
            $sCaller = $psOverrideName;
            if ($psOverrideName == null) {
                $aCaller = cDebug::get_caller(1);
                $sFunc = $aCaller['function'];
                $sClass = '';
                if (isset($aCaller['class'])) {
                    $sClass = $aCaller['class'];
                }
                $sCaller = "$sClass.$sFunc";
            }
            if (! self::$b_output_js) {
                self::$b_output_js = true;
?>
                <script>
                    //BEGIN tracing JS
                    $(function() {
                        $('.<?= self::CSS_CLASS_NAME ?>').on('click', function() {
                            const targetId = $(this).data('target');
                            $('#' + targetId).toggle(); // Automatically opens if hidden, closes if shown
                        });
                    });
                </script>
<?php
            }

            $sID = cCommon::random_string(12);
            cDebug::extra_debug(
                "<div><button class='" . self::CSS_CLASS_NAME . "' data-target='$sID'>X</button><span id='$sID'><span class='tracing'>Enter &gt; {$sCaller}</span>",
                $pbOnce
            );
            self::$ENTER_DEPTH++;
        }
    }

    //**************************************************************************
    public static function leave($psOverrideName = null, $pbOnce = false) {
        if (cDebug::is_extra_debugging() || $pbOnce) {

            $sCaller = $psOverrideName;
            if ($psOverrideName == null) {
                $aCaller = cDebug::get_caller(1);
                $sFunc = $aCaller['function'];
                $sClass = '';
                if (isset($aCaller['class'])) {
                    $sClass = $aCaller['class'];
                }
                $sCaller = "$sClass.$sFunc";
            }
            self::$ENTER_DEPTH--;
            if (self::$ENTER_DEPTH < 0) {
                self::$ENTER_DEPTH = 0;
                //self::write("too many leave calls");
            }
            cDebug::extra_debug(
                "<span class='tracing'>Leave &gt; {$sCaller}</span></span></div>",
                $pbOnce
            );
        }
    }
}

class cDebug {
    private static $DEBUGGING = false;
    private static $EXTRA_DEBUGGING = false;
    private static $MEM_DEBUGGING = false;

    static $SHOW_SQL = false;
    public static $IGNORE_CACHE = false;
    public static $IGNORE_SESSION_USER = false;
    private static $aThings = [];
    const EXTRA_DEBUGGING_SYMBOL = "&#10070;";
    private static $one_time_debug = false;


    //##############################################################################
    public static function is_debugging() {
        $bOnce = self::$one_time_debug;
        self::$one_time_debug = false;
        return (self::$DEBUGGING || self::is_extra_debugging() || $bOnce);
    }

    public static function is_extra_debugging() {
        return self::$EXTRA_DEBUGGING;
    }

    public static function on($pbExtraDebugging = false) {
        ini_set("display_errors", "on"); //needed otherwise 500 error in prod
        self::$DEBUGGING = true;
        self::$EXTRA_DEBUGGING = $pbExtraDebugging;
        cDebug::extra_debug("extra debugging is on");   //will only print if it is on

        if (!cSession::is_session_started()) {
            session_start();
            cDebug::extra_debug("session status is not active - starting session:");
        }
    }

    public static function off() {
        self::write("Debugging off");
        self::$DEBUGGING = false;
        self::$EXTRA_DEBUGGING = false;
    }

    //##############################################################################
    public static function extra_debug_warning($psThing) {
        self::extra_debug("<span class='debug_extra_warning'>$psThing</span>");
    }

    //**************************************************************************
    public static function extra_debug($psThing, $pbOnce = false) {
        if (!self::is_extra_debugging()) return;
        if ($pbOnce) {
            if (array_key_exists($psThing, self::$aThings))
                return;
            else
                self::$aThings[$psThing] = 1;
        }

        $sIndented = self::pr_indent(self::EXTRA_DEBUGGING_SYMBOL . " " . $psThing);
        if (cCommonEnvironment::is_cli())
            echo "{$sIndented}\n";
        else
            echo "<div class='debug_extra'><code>{$sIndented}</code></div>\n";

        self::flush();
    }


    //**************************************************************************
    public static function write($poThing) {
        if (self::is_debugging()) {
            $sIndented = self::pr_indent($poThing);
            if (cCommonEnvironment::is_cli())
                print $sIndented . "\n";
            else
                echo "<div class='debug'><code>{$sIndented}</code></div>";

            self::flush();
        }
    }

    //##############################################################################
    public static function flush() {
        @ob_flush();
        @flush();
    }

    //**************************************************************************
    public static function vardump($poThing, $pbForce = false) {
        if (self::is_extra_debugging() || $pbForce) {
            ob_start();                  // Start output buffering
            var_dump($poThing);         // Dump the variable
            $sRaw = ob_get_clean();    // Get buffer contents and clean buffer
            echo "<div><PRE style='background-color: #f0f0f0;font-name: courier;'>";
            echo (htmlspecialchars($sRaw)); // Encode for HTML
            cCommon::flushprint("</PRE></div>");
        } else
            self::write(__FUNCTION__ . " only available in debug2");
    }

    //**************************************************************************
    public static function get_object_type(object $poThing) {
        $oReflect = new ReflectionClass($poThing);
        return $oReflect->getName();
    }

    //**************************************************************************
    public static function vardump_class($psClassName) {
        cTracing::enter();

        //--------check if class actually exists
        if (!class_exists($psClassName))
            self::error("class $psClassName doesnt exist");

        //--------look inside the class
        if (self::is_extra_debugging()) {
            $oReflect = new ReflectionClass($psClassName);
            self::write($oReflect);
        } else
            self::write(__FUNCTION__ . " only available in debug2");
        cTracing::leave();
    }

    //**************************************************************************
    public static function error($psText, $pbIsSilent = false) {
        try {
            $aCaller = self::get_caller(1);
            $sFunc = @$aCaller['function'];
            $sClass = @$aCaller['class'];
            $sLine = @$aCaller['line'];
        } catch (Exception $e) {
        }
        error_log("$sClass:$sFunc (line $sLine) error: $psText");

        if (!$pbIsSilent)
            self::write("<b><font size='+2'>in <font color='brick'>$sClass:$sFunc (line $sLine)</font> error: <font color='brick'>$psText</font></font></b><pre>");
        throw new DebugException($psText);
    }

    private static function pr__check_param(string $psName): bool {
        $bOut = false;
        if (cCommonHeader::is_set($psName)) {
            $bOut = true;
            self::write("$psName is on");
        } else
            self::write("$psName option is available");
        return $bOut;
    }

    //##############################################################################
    public static function check_GET_or_POST() {
        global $_GET, $_POST, $_SERVER;

        if (cCommonHeader::is_set("debug")) {
            self::on();
            self::write("Debugging is on");
        }


        if (cCommonHeader::is_set("debug2")) {
            self::on(true);
            self::write("Extra debugging is on - shown by " . self::EXTRA_DEBUGGING_SYMBOL);
            self::write("URI is " . $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"]);

            self::$SHOW_SQL = self::pr__check_param("showsql");
        } else
            self::write("for extra debugging use debug2");

        self::$IGNORE_CACHE = self::pr__check_param("nocache");
        self::$IGNORE_SESSION_USER = self::pr__check_param("nouser");
        self::$MEM_DEBUGGING = self::pr__check_param("mem");
    }

    //##############################################################################
    static function get_caller($piLimit = 0) {
        return @debug_backtrace(0, $piLimit + 2)[$piLimit + 1];
    }

    //**************************************************************************
    private static function pr_stacktrace() {
        if (self::is_extra_debugging()) {
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
        } else
            self::write(__FUNCTION__ . " only available in debug2");
    }

    private static function pr_indent($psWhat) {
        $sDate = date('d-m-Y H:i:s');
        $sMem = "";
        if (self::$MEM_DEBUGGING) {
            $iMem = memory_get_usage();
            $sMem = cCommon::human_number($iMem);
            $sMem = " - $sMem - ";
        }

        return str_repeat("&nbsp;", cTracing::$ENTER_DEPTH * 4) . "{$sDate}: {$sMem} {$psWhat}";
    }
}


//check if debugging is needed
cDebug::check_GET_or_POST();
