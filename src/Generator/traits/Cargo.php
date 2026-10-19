<?php

namespace EDI\Generator\Traits;

use EDI\Generator\Message;

/**
 * Weight, VGM, cargo description, dangerous goods, temperature and over-dimension
 * details of a container. Used by Coparn and Coprar\Container.
 *
 * @package EDI\Generator\Traits
 */
trait Cargo {
    /** @var array|null MEA segment with the gross weight or the VGM */
    private $weight;
    /** @var array|null DTM segment, qualifier 798 (VGM determination time) */
    private $weightTime;
    /** @var array|null FTX segment, qualifier AAA (cargo description) */
    private $cargo;
    /** @var array[]|null DGS segments, one per dangerous good */
    private $dangerous;
    /** @var array|null TMP segment with the set temperature */
    private $temperature;
    /** @var array[]|null DIM segments with the over-dimensions */
    private $dimensions;

    /**
     * Sets the verified gross mass (MEA segment, AAE+VGM) and when it was determined (DTM segment, 798).
     *
     * @param string|int|float $weight     verified gross mass in KGM
     * @param string           $weightTime determination time as CCYYMMDDHHMM
     *
     * @return $this
     */
    public function setVGM($weight, $weightTime) {
        $this->weight = ['MEA', 'AAE', 'VGM', ['KGM', $weight]];
        $this->weightTime = Message::dtmSegment(798, $weightTime);
        return $this;
    }

    /**
     * Sets the gross weight (MEA segment, AAE+G). Replaces a VGM set before.
     *
     * @param string|int|float $weight gross weight in KGM
     *
     * @return $this
     */
    public function setGrossWeight($weight) {
        $this->weight = ['MEA', 'AAE', 'G', ['KGM', $weight]];
        return $this;
    }

    /**
     * Sets the cargo description (FTX segment, qualifier AAA).
     *
     * @param string $text free text, e.g. the cargo category
     *
     * @return $this
     */
    public function setCargoCategory($text) {
        $this->cargo = ['FTX', 'AAA', '', '', $text];
        return $this;
    }

    /**
     * Adds a dangerous good without flashpoint and packing group (DGS segment).
     *
     * @deprecated use addDangerous()
     *
     * @param string|int $hazardClass IMDG hazard class
     * @param string|int $hazardCode  UN number
     *
     * @return $this
     */
    public function setDangerous($hazardClass, $hazardCode) {
        $this->addDangerous($hazardClass, $hazardCode);
        return $this;
    }

    /**
     * Adds a dangerous good (DGS segment, regulation IMD = IMDG code).
     *
     * @param string|int            $hazardClass  IMDG hazard class
     * @param string|int            $hazardCode   UN number
     * @param string|int|float|null $flashpoint   flashpoint in degrees Celsius, omitted when null
     * @param string|null           $packingGroup packing group (e.g. I, II, III), omitted when null
     *
     * @return $this
     */
    public function addDangerous($hazardClass, $hazardCode, $flashpoint = null, $packingGroup = null) {
        if ($this->dangerous === null) {
            $this->dangerous = [];
        }

        $dgs = ['DGS', 'IMD', $hazardClass, $hazardCode];
        if ($flashpoint !== null) {
            $dgs[] = [$flashpoint, 'CEL'];
        } elseif ($packingGroup !== null) {
            $dgs[] = '';
        }
        if ($packingGroup !== null) {
            $dgs[] = $packingGroup;
        }

        $this->dangerous[] = $dgs;
        return $this;
    }

    /**
     * Sets the transport temperature (TMP segment, qualifier 2).
     *
     * @param string|int|float $setDegrees set temperature in degrees Celsius
     *
     * @return $this
     */
    public function setTemperature($setDegrees) {
        $this->temperature = ['TMP', '2', [$setDegrees, 'CEL']];
        return $this;
    }

    /**
     * Sets the over-dimensions in centimetres (DIM segments); empty values are omitted.
     * Replaces over-dimensions set before.
     *
     * @param string|int $front  excess length front (DIM 5)
     * @param string|int $back   excess length back (DIM 6)
     * @param string|int $right  excess width right (DIM 7)
     * @param string|int $left   excess width left (DIM 8)
     * @param string|int $height excess height (DIM 13)
     *
     * @return $this
     */
    public function setOverDimensions($front = '', $back = '', $right = '', $left = '', $height = '') {
        $this->dimensions = [];
        if ($front !== '') {
            $this->dimensions[] = ['DIM', '5', ['CMT', $front]];
        }
        if ($back !== '') {
            $this->dimensions[] = ['DIM', '6', ['CMT', $back]];
        }
        if ($right !== '') {
            $this->dimensions[] = ['DIM', '7', ['CMT', '', $right]];
        }
        if ($left !== '') {
            $this->dimensions[] = ['DIM', '8', ['CMT', '', $left]];
        }
        if ($height !== '') {
            $this->dimensions[] = ['DIM', '13', ['CMT', '', '', $height]];
        }
        return $this;
    }
}
