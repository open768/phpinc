<?php
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//% OBJSTORE - simplistic store objects without a database!
//%
//% Problem - creates thousands files on busy websites that exceed inode quotas.
//%  so reduce this SQLlite3
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

require_once  "$phpInc/ckinc/gz.php";
require_once  "$phpInc/ckinc/common.php";
require_once  "$phpInc/ckinc/hash.php";
require_once  "$phpInc/ckinc/objstoredb.php";

cObjStore::$rootFolder = "$root/[objdata]";

//TBD functions to be made non static so that a different realm can be used by different 
class cObjStore
{
    public static $rootFolder = "";
    public static $OBJDATA_REALM = null;
    public static $obsolete_message_sent = false;

    //#####################################################################
    //# PRIVATES
    //#####################################################################
    private static function pr_get_folder_path($psFolder)
    {
        if (self::$OBJDATA_REALM == null)
            cDebug::error("OBJDATA_REALM not set in objstore");
        $sOut = self::$rootFolder . "/" . self::$OBJDATA_REALM;
        if ($psFolder) $sOut .= "/$psFolder";
        return $sOut;
    }

    //********************************************************************
    private static function pr_show_obsolete_msg()
    {
        if (!self::$obsolete_message_sent) {
            cPageOutput::warning("cObjStore is deprecated, use cObjStoreDb instead");
            self::$obsolete_message_sent = true;
        }
    }

    //#####################################################################
    //# PUBLIC
    //#####################################################################
    public static function kill_folder($psFolder)
    {
        cDebug::enter();

        self::pr_show_obsolete_msg();
        $sPath = self::pr_get_folder_path($psFolder);
        cDebug::write("killing folder $sPath");
        cCommon::delTree($sPath);

        cDebug::leave();
    }

    //********************************************************************
    public static function kill_file($psFolder, $psFile)
    {
        cDebug::enter();

        self::pr_show_obsolete_msg();
        $num_args = func_num_args();
        if ($num_args != 2) cDebug::error("kill_file: incorrect number of arguments - expected 2 got $num_args ");

        $folder = self::pr_get_folder_path($psFolder);
        $file = "$folder/$psFile";
        if (file_exists($file)) {
            unlink($file);
            cDebug::write("deleted file $file");
        }
        cDebug::leave();
    }

    //********************************************************************
    public static function file_exists($psFolder, $psFile)
    {
        cDebug::enter();

        self::pr_show_obsolete_msg();
        $sFolder = self::pr_get_folder_path($psFolder);
        $sFile = "$sFolder/$psFile";

        $bResult = file_exists($sFile);
        if (!$bResult) cDebug::extra_debug("file doesnt exist : $sFile");

        cDebug::leave();
        return $bResult;
    }

    //********************************************************************
    public static function get_file($psFolder, $psFile)
    {
        $aData = null;
        cDebug::enter();

        self::pr_show_obsolete_msg();
        $num_args = func_num_args();
        if ($num_args != 2) cDebug::error("get_file: incorrect number of arguments - expected 2 got $num_args ");

        $sFolder = self::pr_get_folder_path($psFolder);
        $sFile = "$sFolder/$psFile";

        if (file_exists($sFile)) {
            $aData = cGzip::readObj($sFile);
        } elseif (cHash::exists($sFile)) {
            cDebug::write("found in hash");
            $aData = cHash::get($sFile);
        }

        cDebug::leave();
        return $aData;
    }

    //********************************************************************
    public static function put_file($psFolder, $psFile, $poData)
    {
        cDebug::enter();

        self::pr_show_obsolete_msg();
        $num_args = func_num_args();
        if ($num_args != 3) cDebug::error("put_file: incorrect number of arguments - expected 3 got $num_args ");

        //check that there is something to write
        if ($poData == null) cDebug::error("put_file: no data to write");

        $sFolder = self::pr_get_folder_path($psFolder);
        if (!is_dir($sFolder)) {
            cDebug::write("making folder: for hash $psFolder");
            mkdir($sFolder, 0700, true);
        }

        $sFile = "$sFolder/$psFile";
        cDebug::write("writing to: $sFile");
        cGzip::writeObj($sFile, $poData);

        cDebug::leave();
    }

    //********************************************************************
    static function push_to_array($psFolder, $psFile, $poData)
    {
        cDebug::enter();
        //always get the latest file
        $aData = self::get_file($psFolder, $psFile);
        //update the data
        if (!$aData) $aData = [];
        $aData[] = $poData; //add to the array
        //put the data back
        self::put_file($psFolder, $psFile, $aData);

        cDebug::leave();
        return $aData;
    }
}
