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


class cImageFunctions {
    //************************************************************************************
    static function crop($poImg, $piX, $piY, $piWidth, $piHeight, $piQuality, $psOutFile) {
        cDebug::write("cropping to $piX, $piY");

        $oDest = imagecreatetruecolor($piWidth, $piHeight);
        cDebug::write("cropping ($piX, $piY), w=" . $piWidth . " h=" . $piHeight);
        imagecopy($oDest, $poImg, 0, 0, $piX, $piY, $piWidth, $piHeight);

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

    //************************************************************************************
    private static function pr_make_thumbnail_obj(string $psImgUrl, int $piHeight, int $piQuality) {
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
    static function make_thumbnail_blob(string $psImgUrl, int $piHeight, int $piQuality) {
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
    static function make_thumbnail(string $psImgUrl, int $piHeight, int $piQuality, string $psOutFilename) {
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
