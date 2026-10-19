<?php

namespace EDI\Generator\Traits;

use EDI\Generator\EdifactException;

/**
 * Transport details (TDT segment). Used by Desadv and Orders.
 *
 * @package EDI\Generator\Traits
 */
trait TransportData {
    /** @var array|null TDT segment */
    protected $transportData;

    /**
     * Returns the transport details (TDT segment), or null if not set.
     *
     * @return array|null
     */
    public function getTransport() {
        return $this->transportData;
    }

    /**
     * Sets the transport details (TDT segment, stage qualifier 13).
     *
     * @param string     $trackingCode    conveyance reference, e.g. the tracking number
     * @param int|string $type            mode of transport: 10, 20, 30 (default), 40, 50, 60, 90
     * @param string     $transportMedium type of means of transport: 31, 31S, 51, 52
     *
     * @return $this
     * @throws EdifactException if the mode or means of transport is not allowed
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
