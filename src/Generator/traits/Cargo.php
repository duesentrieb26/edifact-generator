<?php

namespace EDI\Generator\Traits;

use EDI\Generator\Message;

/**
 * Trait Cargo
 * Weight, cargo and dangerous goods details shared by COPARN and the COPRAR container
 * @package EDI\Generator\Traits
 */
trait Cargo {
    private $weight;
    private $weightTime;
    private $cargo;
    private $dangerous;
    private $temperature;
    private $dimensions;

    /*
     * VGM information
     *
     */
    public function setVGM($weight, $weightTime) {
        $this->weight = ['MEA', 'AAE', 'VGM', ['KGM', $weight]];
        $this->weightTime = Message::dtmSegment(798, $weightTime);
        return $this;
    }

    /*
     * Weight information
     *
     */
    public function setGrossWeight($weight) {
        $this->weight = ['MEA', 'AAE', 'G', ['KGM', $weight]];
        return $this;
    }

    /*
     * Cargo category
     *
     */
    public function setCargoCategory($text) {
        $this->cargo = ['FTX', 'AAA', '', '', $text];
        return $this;
    }

    /*
     * DEPRECATED
     */
    public function setDangerous($hazardClass, $hazardCode) {
        $this->addDangerous($hazardClass, $hazardCode);
        return $this;
    }

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

    public function setTemperature($setDegrees) {
        $this->temperature = ['TMP', '2', [$setDegrees, 'CEL']];
        return $this;
    }

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
