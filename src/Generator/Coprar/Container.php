<?php

namespace EDI\Generator\Coprar;

use EDI\Generator\Traits\BookingAndSeal;
use EDI\Generator\Traits\Cargo;

class Container {
    use BookingAndSeal, Cargo;

    private $cntr;
    private $pol;
    private $pod;
    private $fnd;
    private $containerOperator;

    public function __construct() {
    }

    /*
     * $size = 22G1, 42G1, etc; 306 = smdg, 6436 = ISO spec
     * $statusCode = 1 (Continental), 2 (Export), 3 (Import)
     * $fullEmptyIndicator = 4 (Empty), 5 (Full)
     */
    public function setContainer($number, $size, $statusCode, $fullEmptyIndicator) {
        $this->cntr = \EDI\Generator\Message::eqdSegment('CN', $number, [$size, '102', '5'], '', $statusCode, $fullEmptyIndicator);
        return $this;
    }

    /*
     * Port of Discharge
     *
     */
    public function setPOL($loc) {
        $this->pol = \EDI\Generator\Message::locSegment(9, [$loc, 139, 6]);
        return $this;
    }

    /*
     * Port of Discharge
     *
     */
    public function setPOD($loc) {
        $this->pod = \EDI\Generator\Message::locSegment(11, [$loc, 139, 6]);
        return $this;
    }

    /*
     * Final destination
     *
     */
    public function setFND($loc) {
        $this->fnd = \EDI\Generator\Message::locSegment(7, [$loc, 139, 6]);
        return $this;
    }

    /*
     * $line: Master Liner Codes List
     */
    public function setContainerOperator($line) {
        $this->containerOperator = ['NAD', 'CF', [$line, 160, 20]];
        return $this;
    }

    public function compose() {
        $composed = [$this->cntr];
        if ($this->bkg !== null) {
            $composed[] = $this->bkg;
        }
        if ($this->weightTime !== null) {
            $composed[] = $this->weightTime;
        }
        if ($this->pol !== null) {
            $composed[] = $this->pol;
        }
        if ($this->pod !== null) {
            $composed[] = $this->pod;
        }
        $composed[] = $this->fnd;
        $composed[] = $this->weight;
        if ($this->seal !== null) {
            $composed[] = $this->seal;
        }
        if ($this->dimensions !== null) {
            foreach ($this->dimensions as $segment) {
                $composed[] = $segment;
            }
        }
        if ($this->temperature !== null) {
            $composed[] = $this->temperature;
        }
        if ($this->cargo !== null) {
            $composed[] = $this->cargo;
        }
        if ($this->dangerous !== null) {
            foreach ($this->dangerous as $segment) {
                $composed[] = $segment;
            }
        }
        $composed[] = $this->containerOperator;

        return $composed;
    }
}
