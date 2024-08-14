<?php

namespace EDI\Generator\Traits;

use EDI\Generator\EdifactException;

/**
 * Trait TransportData
 * @package EDI\Generator\Traits
 */
trait TransportData {
    /** @var array */
    protected $transportData;

    /**
     * @return array
     */
    public function getTransport() {
        return $this->transportData;
    }

    /**
     * @param string $trackingCode
     * @param int $type 10, 20, 30, 40, 50, 60, 90
     * @param string $transportMedium 31, 31S, 51, 52
     * @return self
     * @throws EdifactException
     */
    public function setTransportData($trackingCode, $type = 30, $transportMedium = '31') {
        $this->isAllowed($type, [
            10,
            20,
            30, // DEFAULT
            40,
            50,
            60,
            90
        ]);

        if (!in_array($transportMedium, ['31', '31S', '51', '52'])) {
            throw new EdifactException('value: ' . $transportMedium . ' is not in allowed values:  [31, 31S, 51, 52] in ' . __METHOD__);
        }

        $this->transportData = ['TDT', '13', $trackingCode, $type, $transportMedium];

        return $this;
    }
}
