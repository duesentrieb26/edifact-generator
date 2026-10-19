<?php

namespace EDI\Generator\Coprar;

use EDI\Generator\Traits\BookingAndSeal;
use EDI\Generator\Traits\Cargo;

/**
 * One container of a COPRAR message: the EQD segment group with booking, ports, weight, seal,
 * dimensions, temperature, cargo, dangerous goods and container operator.
 *
 * @package EDI\Generator\Coprar
 */
class Container {
    use BookingAndSeal, Cargo;

    /** @var array|null EQD+CN segment, container number, size and status */
    private $cntr;
    /** @var array|null LOC+9 segment, port of loading */
    private $pol;
    /** @var array|null LOC+11 segment, port of discharge */
    private $pod;
    /** @var array|null LOC+7 segment, final destination */
    private $fnd;
    /** @var array|null NAD+CF segment, container operator */
    private $containerOperator;

    /**
     * Creates an empty container.
     */
    public function __construct() {
    }

    /**
     * Sets the container (EQD+CN segment).
     *
     * @param string $number             container number
     * @param string $size               size and type code, e.g. 22G1, 42G1 (code list 102, agency 5 = ISO)
     * @param int    $statusCode         equipment status (DE 8249): 1 = continental, 2 = export, 3 = import
     * @param int    $fullEmptyIndicator full/empty indicator (DE 8169): 4 = empty, 5 = full
     *
     * @return $this
     */
    public function setContainer($number, $size, $statusCode, $fullEmptyIndicator) {
        $this->cntr = \EDI\Generator\Message::eqdSegment('CN', $number, [$size, '102', '5'], '', $statusCode, $fullEmptyIndicator);
        return $this;
    }

    /**
     * Sets the port of loading (LOC+9 segment).
     *
     * @param string $loc UN/LOCODE of the port
     *
     * @return $this
     */
    public function setPOL($loc) {
        $this->pol = \EDI\Generator\Message::locSegment(9, [$loc, 139, 6]);
        return $this;
    }

    /**
     * Sets the port of discharge (LOC+11 segment).
     *
     * @param string $loc UN/LOCODE of the port
     *
     * @return $this
     */
    public function setPOD($loc) {
        $this->pod = \EDI\Generator\Message::locSegment(11, [$loc, 139, 6]);
        return $this;
    }

    /**
     * Sets the final destination (LOC+7 segment).
     *
     * @param string $loc UN/LOCODE of the place
     *
     * @return $this
     */
    public function setFND($loc) {
        $this->fnd = \EDI\Generator\Message::locSegment(7, [$loc, 139, 6]);
        return $this;
    }

    /**
     * Sets the container operator (NAD+CF segment).
     *
     * @param string $line carrier code from the SMDG Master Liner Codes List (code list 160, agency 20)
     *
     * @return $this
     */
    public function setContainerOperator($line) {
        $this->containerOperator = ['NAD', 'CF', [$line, 160, 20]];
        return $this;
    }

    /**
     * Returns the container segments in message order (EQD, RFF, DTM, LOC, MEA, SEL, DIM, TMP, FTX, DGS, NAD).
     *
     * @return array list of segments
     */
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
