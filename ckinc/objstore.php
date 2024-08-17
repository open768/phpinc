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


//TBD functions to be made non static so that a different realm can be used by different 
class cObjStore {
    public static $rootFolder = "";
    public static $OBJDATA_REALM = null;
    public static $obsolete_message_sent = false;

    //#####################################################################
    //# PRIVATES
    //#####################################################################
    static function get_folder_path($psFolder = null) {
        if (self::$OBJDATA_REALM == null)
            cDebug::error("OBJDATA_REALM not set in objstore");
        $sOut = self::$rootFolder . "/" . self::$OBJDATA_REALM;
        if ($psFolder) $sOut .= "/$psFolder";
        return $sOut;
    }

    //********************************************************************
    private static function pr_show_obsolete_msg() {
        if (!self::$obsolete_message_sent) {
            cPageOutput::warning("cObjStore is deprecated, use cObjStoreDb instead");
            self::$obsolete_message_sent = true;
        }
    }

    //#####################################################################
    //# PUBLIC
    //#####################################################################
    public static function backup() {
        global $root;

        cPageOutput::warning("objdata (used by cObjStore) backup to be deprecated");

        $sFilename = addslashes("$root/backup.zip");
        $sFolder = addslashes(self::$rootFolder);
        $cmd = "zip -r \"$sFilename\" \"$sFolder\"";
        cDebug::write("running command $cmd");
        $iReturn = 0;
        echo exec($cmd, $output, $iReturn);
        cDebug::write("done: $iReturn");
        cDebug::vardump($output);
    }

    //********************************************************************
    public static function kill_folder($psFolder) {
        cDebug::enter();

        self::pr_show_obsolete_msg();
        $sPath = self::get_folder_path($psFolder);
        cDebug::write("killing folder $sPath");
        cCommonFiles::delTree($sPath);

        cDebug::leave();
    }

    //********************************************************************
    public static function kill_file($psFolder, $psFile) {
        //cDebug::enter();

        self::pr_show_obsolete_msg();
        $num_args = func_num_args();
        if ($num_args != 2) cDebug::error("kill_file: incorrect number of arguments - expected 2 got $num_args ");

        $folder = self::get_folder_path($psFolder);
        $file = "$folder/$psFile";
        if (file_exists($file)) {
            @unlink($file);
            cDebug::write("deleted file $file");
        }
        //cDebug::leave();
    }

    //********************************************************************
    public static function file_exists($psFolder, $psFile) {
        self::pr_show_obsolete_msg();
        $sFolder = self::get_folder_path($psFolder);
        $sFile = "$sFolder/$psFile";

        $bResult = file_exists($sFile);

        return $bResult;
    }

    //********************************************************************
    public static function get_file($psFolder, $psFile) {
        $aData = null;
        //cDebug::enter();

        self::pr_show_obsolete_msg();
        $num_args = func_num_args();
        if ($num_args != 2) cDebug::error("get_file: incorrect number of arguments - expected 2 got $num_args ");

        $sFolder = self::get_folder_path($psFolder);
        $sFile = "$sFolder/$psFile";
        $sReal = realpath($sFile);

        if ($sReal) {
            cDebug::write("file exists: $sReal");
            $aData = cGzip::readObj($sReal);
        } elseif (cHash::exists($sFile)) {
            cDebug::write("found in hash");
            $aData = cHash::get($sFile);
        }

        //cDebug::leave();
        return $aData;
    }

    //********************************************************************
    public static function put_file($psFolder, $psFile, $poData) {
        cDebug::enter();

        self::pr_show_obsolete_msg();
        $num_args = func_num_args();
        if ($num_args != 3) cDebug::error("put_file: incorrect number of arguments - expected 3 got $num_args ");

        //check that there is something to write
        if ($poData == null) cDebug::error("put_file: no data to write");

        $sFolder = self::get_folder_path($psFolder);
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
    static function push_to_array($psFolder, $psFile, $poData) {
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
cObjStore::$rootFolder = "$root/[objdata]";
