<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 -2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
 **************************************************************************/

class cPoint {
    public $x, $y;

    public function __construct($pX, $pY) {
        $this->x = $pX;
        $this->y = $pY;
    }
}

class cRect {
    /** @var cPoint $P1 */
    /** @var cPoint $P2 */
    public $P1, $P2;
    public function __construct($pX1, $pY1, $pX2, $pY2) {
        $this->P1 = new cPoint((float)$pX1, (float)$pY1);
        $this->P2 = new cPoint((float)$pX2, (float)$pY2);
    }

    public function merge($poRect2) {
        if (!is_a($poRect2, "cRect")) cDebug::error("wrong type - must be a cRect");
        $this->P1->x = min($this->P1->x, $poRect2->P1->x);
        $this->P1->y = min($this->P1->y, $poRect2->P1->y);
        $this->P2->x = max($this->P2->x, $poRect2->P2->x);
        $this->P2->y = max($this->P2->y, $poRect2->P2->y);
    }

    public function intersects($poRect2) {
        if (!is_a($poRect2, "cRect")) cDebug::error("wrong type - must be a cRect - was a " . gettype($poRect2));

        if (($this->P2->x < $poRect2->P1->x) || ($this->P1->x > $poRect2->P2->x)) return false;
        if (($this->P2->y < $poRect2->P1->y) || ($this->P1->y > $poRect2->P2->y)) return false;

        return true;
    }

    /**
     * expands the bounding box of the rectangle
     * 
     * @param float $pfX
     * @param float $pfy
     * 
     * @return void
     */
    public function expand($pfX, $pfY) {
        $this->P1->x = min($this->P1->x, $pfX);
        $this->P1->y = min($this->P1->y, $pfY);
        $this->P2->x = max($this->P1->x, $pfX);
        $this->P2->y = max($this->P1->y, $pfY);
    }
}
