<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2024
This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
//
 **************************************************************************/
class cThumbNailer {
    static $blobber = null;
    const BLOB_MIME_TYPE = "image/jpeg";
    const BLOBBER_DB = "thumbblobs.db";

    static function init_blobber() {
        if (self::$blobber == null) self::$blobber = new cBlobber(self::BLOBBER_DB);
    }

    //************************************************************************************
    //* thumbnail
    //************************************************************************************
    private static function pr_make_thumbnail_obj(string $psImgUrl, int $piHeight, int $piQuality): GdImage {
        //----get the original image --------------------------------------------------------
        cDebug::write("fetching $psImgUrl");
        $oHttp = new cHttp();
        $oImg = $oHttp->fetch_image($psImgUrl);
        cDebug::write("got image $psImgUrl");

        try {
            //----work out new width --------------------------------------------------------
            $iWidth = imagesx($oImg);
            $iHeight = imagesy($oImg);
            $iNewWidth = floor($iWidth * $piHeight / $iHeight);

            //----resize image --------------------------------------------------------
            cDebug::write("new Width is $iNewWidth .. resizing");
            $oThumb = imagecreatetruecolor($iNewWidth, $piHeight);
            imagecopyresampled($oThumb, $oImg, 0, 0, 0, 0, $iNewWidth, $piHeight, $iWidth, $iHeight);
        } finally {
            imagedestroy($oImg);
        }

        return $oThumb;
    }

    //************************************************************************************
    static function get_thumbnail_blob_data(string $psImgUrl, int $piHeight, int $piQuality) {
        $aData = null;
        $oBlobber = self::$blobber;
        if (!$oBlobber->exists($psImgUrl)) {
            $sBlob = self::pr_make_thumbnail_blob($psImgUrl, $piHeight, $piQuality);
            $oBlobber->put_obj($psImgUrl, self::BLOB_MIME_TYPE, $sBlob);
        }
        cDebug::write("getting data");
        $aData = $oBlobber->get($psImgUrl);
        return $aData;
    }

    //************************************************************************************
    private static function pr_make_thumbnail_blob(string $psImgUrl, int $piHeight, int $piQuality): string {
        $oData = null;
        cDebug::enter();
        $oThumb = self::pr_make_thumbnail_obj($psImgUrl, $piHeight, $piQuality);

        try {
            ob_start();
            try {
                imagejpeg($oThumb, null, $piQuality);
                $oData = ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($oThumb);
        }
        cDebug::leave();
        return $oData;
    }

    //************************************************************************************
    static function make_thumbnail_file(string $psImgUrl, int $piHeight, int $piQuality, string $psOutFilename) {
        //dont generate a thumbnail that allready exists
        if (file_exists($psOutFilename))
            return;

        $oThumb = self::pr_make_thumbnail_obj($psImgUrl, $piHeight, $piQuality);
        try {
            //------------------WRITE IT OUT
            $sFolder = dirname($psOutFilename);
            if (!file_exists($sFolder)) {
                cDebug::write("creating folder: $sFolder");
                mkdir($sFolder, 0755, true); //in case folder needs to readable by apache
            }
            imagejpeg($oThumb, $psOutFilename, $piQuality);
        } finally {
            imagedestroy($oThumb);
        }
    }
}
cThumbNailer::init_blobber();

//####################################################################################
//#
//####################################################################################
class cCropper {
    static $blobber = null;
    const BLOB_PREFIX = "CROP:";
    const BLOB_MIME_TYPE = "image/jpeg";
    const BLOBBER_DB = "cropblobs.db";
    const JPEG_QUALITY = 90;

    static function init_blobber() {
        if (self::$blobber == null) self::$blobber = new cBlobber(self::BLOBBER_DB);
    }

    //************************************************************************************
    static function get_crop_blob_data(string $psImgUrl, int $piLeft, int $piTop, int $piWidth, int $piHeight) {
        cDebug::enter();
        $sKey = self::BLOB_PREFIX . "{$psImgUrl}/{$piLeft}/{$piTop}/{$piWidth}/{$piHeight}";
        $oBlobber = self::$blobber;
        if (!$oBlobber->exists($sKey)) {
            $sBlob = self::pr_make_crop_blob($psImgUrl,  $piLeft, $piTop, $piWidth, $piHeight);
            $oBlobber->put_obj($sKey, self::BLOB_MIME_TYPE, $sBlob);
        }
        cDebug::write("getting data");
        $aData = $oBlobber->get($sKey);
        cDebug::leave();
        return $aData;
    }

    //************************************************************************************
    private static function pr_make_crop_blob(string $psUrl, int $piLeft, int $piTop, int $piWidth, int $piHeight): string {
        cDebug::enter();
        $oSource = null;
        $oCropped = null;
        $oJpgData = null;

        //------------------fetch the source image
        $oHttp = new cHttp;
        $oSource = $oHttp->fetch_image($psUrl);
        if ($oSource == null) cDebug::error("unable to fetch image $psUrl");

        try {
            //-------- crop the image
            $oCropped = self::pr_make_crop_obj($oSource, $piLeft, $piTop, $piWidth, $piHeight);

            //------capture the image from the buffer
            try {
                ob_start();
                try {
                    imagejpeg($oCropped, null, self::JPEG_QUALITY);
                    $oJpgData = ob_get_contents();
                } finally {
                    ob_end_clean();
                }
            } finally {
                imagedestroy($oCropped);
            }
        } finally {
            imagedestroy($oSource);
        }

        cDebug::leave();
        return $oJpgData;
    }


    //************************************************************************************
    private static function pr_make_crop_obj(\GdImage $poImg, int $piLeft, int $piTop, int $piWidth, int $piHeight): \GdImage {
        cDebug::enter();
        $oDest = imagecreatetruecolor($piWidth, $piHeight);
        cDebug::write("cropping ($piLeft, $piTop), w=" . $piWidth . " h=" . $piHeight);
        imagecopy($oDest, $poImg, 0, 0, $piLeft, $piTop, $piWidth, $piHeight);
        cDebug::leave();
        return $oDest;
    }

    //************************************************************************************
    static function crop_to_file(\GdImage $poImg, int $piX, int $piY, int $piWidth, int $piHeight, int $piQuality, $psOutFile) {
        cDebug::write("cropping to $piX, $piY");

        $oDest = self::pr_make_crop_obj($poImg,  $piX,  $piY,  $piWidth,  $piHeight);

        //write out the file
        $sFolder = dirname($psOutFile);
        if (!file_exists($sFolder)) {
            cDebug::write("creating folder: $sFolder");
            mkdir($sFolder, 0755, true); //folder needs to readable by apache
        }

        cDebug::write("writing jpeg to $psOutFile");
        imagejpeg($oDest, $psOutFile, $piQuality);
        imagedestroy($oDest);
    }
}
cCropper::init_blobber();
