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
//###########################################################################################
//#
//###########################################################################################
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
        cTracing::enter();
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
        cTracing::leave();
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
class cCropData {
    public cBlobData $blob;
    public string $img_url;
    public int $left;
    public int $top;
    public int $width;
    public int $height;
}

class cCropper {
    static $blobber = null;
    const BLOB_PREFIX = "CROP:";
    const BLOB_MIME_TYPE = "image/jpeg";
    const BLOBBER_DB = "cropblobs.db";
    const JPEG_QUALITY = 100;

    static function init_blobber() {
        if (self::$blobber == null) self::$blobber = new cBlobber(self::BLOBBER_DB);
    }

    //************************************************************************************
    static function get_crop_blob_data(string $psImgUrl, int $piLeft, int $piTop, int $piWidth, int $piHeight): cCropData {
        //cTracing::enter();
        $sKey = self::BLOB_PREFIX . "{$psImgUrl}/{$piLeft}/{$piTop}/{$piWidth}/{$piHeight}";
        $oBlobber = self::$blobber;
        if (!$oBlobber->exists($sKey)) {
            cDebug::write("generating crop");
            $sBlob = self::pr_make_crop_blob($psImgUrl,  $piLeft, $piTop, $piWidth, $piHeight);
            $oBlobber->put_obj($sKey, self::BLOB_MIME_TYPE, $sBlob);
        } else
            cDebug::write("crop allready present");

        $oBlob = $oBlobber->get($sKey);
        $oCrop = new cCropData; {
            $oCrop->img_url = $psImgUrl;
            $oCrop->top = $piTop;
            $oCrop->left = $piLeft;
            $oCrop->height = $piHeight;
            $oCrop->width = $piWidth;
            $oCrop->blob = $oBlob;
        }

        //cTracing::leave();
        return $oCrop;
    }

    //************************************************************************************
    private static function pr_make_crop_blob(string $psUrl, int $piLeft, int $piTop, int $piWidth, int $piHeight): string {
        cTracing::enter();
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

        cTracing::leave();
        return $oJpgData;
    }


    //************************************************************************************
    private static function pr_make_crop_obj(\GdImage $poImg, int $piLeft, int $piTop, int $piWidth, int $piHeight): \GdImage {
        cTracing::enter();
        $oDest = imagecreatetruecolor($piWidth, $piHeight);
        cDebug::write("cropping ($piLeft, $piTop), w=" . $piWidth . " h=" . $piHeight);
        imagecopy($oDest, $poImg, 0, 0, $piLeft, $piTop, $piWidth, $piHeight);
        cTracing::leave();
        return $oDest;
    }
}
cCropper::init_blobber();


//###########################################################################################
//#
//###########################################################################################
class cMosaicer {
    const BLOBBER_DB = "mosblobs.db";
    const JPEG_QUALITY = 100;
    const BLOB_MIME_TYPE = "image/jpeg";

    /** @var cBlobber $blobber **/
    static $blobber = null;
    static $BORDER_WIDTH = 5;

    //************************************************************************************
    static function init_blobber() {
        if (self::$blobber == null) self::$blobber = new cBlobber(self::BLOBBER_DB);
    }

    //************************************************************************************
    static function get(string $psKey): cBlobData {
        $oBlobber = self::$blobber;
        return $oBlobber->get($psKey);
    }
    static function delete(string $psKey): void {
        $oBlobber = self::$blobber;
        $oBlobber->remove($psKey);
    }

    static function exists(string $psKey): bool {
        $oBlobber = self::$blobber;
        return $oBlobber->exists($psKey);
    }

    //************************************************************************************
    static function make(string $psKey, array $paBlobs, int $piTileWidth, int $piTileHeight, int $piCols): cBlobData {
        cTracing::enter();

        $oBlobber = self::$blobber;

        //----------check if mosaic exists ------------------------------------------------
        if ($oBlobber->exists($psKey))
            if (!cDebug::$IGNORE_CACHE) {
                cTracing::leave();
                return self::get($psKey);
            } else {
                cDebug::extra_debug("deleting existing mosaic");
                self::delete($psKey);
            }

        //----------generate mosaic ------------------------------------------------
        $iImageCount = count($paBlobs);
        $iCols = $piCols;
        if ($iImageCount < $piCols) {
            $iCols = $iImageCount;
            $iRows = 1;
        } else
            $iRows = ceil($iImageCount  / $piCols);
        cDebug::extra_debug("mosaic has {$iRows} rows and {$iCols} columns");
        $iMosWidth = self::$BORDER_WIDTH  +  $iCols * ($piTileWidth + self::$BORDER_WIDTH);
        $iMosHeight = self::$BORDER_WIDTH +  $iRows * ($piTileHeight + self::$BORDER_WIDTH);
        cDebug::extra_debug("mosaic has {$iMosWidth} width and {$iMosHeight} height");

        $imgMosaic =  imagecreatetruecolor($iMosWidth, $iMosHeight);
        try {
            $iX = self::$BORDER_WIDTH;
            $iY = self::$BORDER_WIDTH;
            $iCol = 1;
            foreach ($paBlobs as $oCrop) {
                $sBlob = $oCrop->blob->blob;
                //- - - - - -blit each blob
                $imgTile = imagecreatefromstring($sBlob);
                try {
                    imagecopy($imgMosaic, $imgTile, $iX, $iY, 0, 0, $piTileWidth, $piTileHeight);
                } finally {
                    imagedestroy($imgTile);
                }

                //- - - - - - position for next image
                $iX += self::$BORDER_WIDTH + $piTileWidth;
                $iCol++;
                if ($iCol > $piCols) {
                    $iX = self::$BORDER_WIDTH;
                    $iY += self::$BORDER_WIDTH + $piTileHeight;
                    $iCol = 1;
                }
            }

            //create a jpeg from the mosaic
            ob_start();
            try {
                imagejpeg($imgMosaic, null, self::JPEG_QUALITY);
                $sJpgData = ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($imgMosaic);
        }

        //------------write the jpeg to the blob
        $oBlobber->put_obj($psKey, self::BLOB_MIME_TYPE, $sJpgData);
        $oBlob =  $oBlobber->get($psKey);

        //----------return the result ------------------------------------------------
        cTracing::leave();
        return $oBlob;
    }
}
cMosaicer::init_blobber();
