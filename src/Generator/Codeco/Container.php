<?php

namespace EDI\Generator\Codeco;

use EDI\Generator\Traits\BookingAndSeal;

/**
 * One container of a CODECO message: the EQD segment group with booking, event date, location,
 * weight, seal and mode of transport.
 *
 * @package EDI\Generator\Codeco
 */
class Container {
    use BookingAndSeal;

    /** @var array|null EQD+CN segment, container number, size and status */
    private $cntr;

    /** @var array|null MEA+AAE segment, container weight */
    private $weight;
    /** @var array|null DTM+7 segment, date of the equipment event */
    private $effectiveDate;

    /** @var array|null TDT+1 segment, inland mode and means of transport */
    private $modeOfTransport;
    /** @var array|null LOC+165 segment, place of delivery */
    private $destination;

    /**
     * Creates an empty container.
     */
    public function __construct() {
    }

    /**
     * Sets the container (EQD+CN segment).
     *
     * @param string $number             container number
     * @param string $size               size and type code, e.g. 22G1, 42G1 (code list 6346 = ISO 6346, agency 306 = SMDG)
     * @param int    $statusCode         equipment status (DE 8249): 1 = continental, 2 = export, 3 = import
     * @param int    $fullEmptyIndicator full/empty indicator (DE 8169): 4 = empty, 5 = full
     *
     * @return $this
     */
    public function setContainer($number, $size, $statusCode, $fullEmptyIndicator) {
        $this->cntr = \EDI\Generator\Message::eqdSegment('CN', $number, [$size, '6346', '306'], '', $statusCode, $fullEmptyIndicator);
        return $this;
    }

    /**
     * Sets the date of the equipment event (DTM+7 segment).
     *
     * @param string|null $date date and time as CCYYMMDDHHMM, now when null
     *
     * @return $this
     */
    public function setEffectiveDate($date = null) {
        if ($date === null) {
            $date = date('YmdHi');
        }
        $this->effectiveDate = \EDI\Generator\Message::dtmSegment(7, $date);
        return $this;
    }

    /**
     * Sets the inland transport (TDT+1 segment).
     *
     * @param int $transportMode  mode of transport (DE 8067): 2 = rail, 3 = road
     * @param int $transportMeans type of means of transport (DE 8179): 25 = train, 31 = truck
     *
     * @return $this
     */
    public function setModeOfTransport($transportMode, $transportMeans) {
        $this->modeOfTransport = \EDI\Generator\Message::tdtShortSegment(1, '', $transportMode, $transportMeans);
        return $this;
    }

    /**
     * Sets the place of delivery (LOC+165 segment).
     *
     * @param string $locode UN/LOCODE of the place
     *
     * @return $this
     */
    public function setLocation($locode) {
        $this->destination = \EDI\Generator\Message::locSegment(165, [$locode, 139, 6]);
        return $this;
    }

    /**
     * Sets the container weight in kilograms (MEA+AAE segment).
     *
     * @param string           $type   measurement type: G = gross mass, VGM = verified gross mass
     * @param string|int|float $weight weight in kilograms
     *
     * @return $this
     */
    public function setWeight($type, $weight) {
        $this->weight = ['MEA', 'AAE', $type, ['KGM', $weight]];
        return $this;
    }

    /**
     * Returns the container segments in message order (EQD, RFF, DTM, LOC, MEA, SEL, TDT).
     *
     * @return array list of segments
     */
    public function compose() {
        $composed = [$this->cntr];
        if ($this->bkg !== null) {
            $composed[] = $this->bkg;
        }
        $composed[] = $this->effectiveDate;
        if ($this->destination !== null) {
            $composed[] = $this->destination;
        }
        if ($this->weight !== null) {
            $composed[] = $this->weight;
        }
        if ($this->seal !== null) {
            $composed[] = $this->seal;
        }
        if ($this->modeOfTransport !== null) {
            $composed[] = $this->modeOfTransport;
        }
        return $composed;
    }
}
