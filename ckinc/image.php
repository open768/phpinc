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
    static function make_thumbnail($psImgUrl, $piHeight, $piQuality, $psOutFilename) {

        //dont generate a thumbnail that allready exists
        if (file_exists($psOutFilename))
            return;

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
            try {
                imagecopyresampled($oThumb, $oImg, 0, 0, 0, 0, $iNewWidth, $piHeight, $iWidth, $iHeight);

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
        } finally {
            imagedestroy($oImg);
        }
    }
}
