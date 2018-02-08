<?php

namespace EDI\Generator\Traits;

use EDI\Generator\EdifactException;

/**
 * Trait TransportData
 * @package EDI\Generator\Traits
 */
trait TransportData
{
    /** @var array */
    protected $transportData;

    /**
     * @return array
     */
    public function getTransport()
    {
        return $this->transportData;
    }

    /**
     * @param string $trackingCode
     * @param int $type
     * @return self
     * @throws EdifactException
     */
    public function setTransportData($trackingCode, $type = 30)
    {
        $this->isAllowed($type, [
            10, 20, 30, 40, 50, 60, 90
        ]);
        $this->transportData = ['TDT', '13', $trackingCode, $type];

        return $this;
    }

}