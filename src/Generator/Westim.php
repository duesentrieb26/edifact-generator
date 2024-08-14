<?php

namespace EDI\Generator;

class Westim extends Message {
    private $_day;
    private $_estimateReference;

    private $_dtmATR;
    private $_currency;
    private $_labourRate;
    private $_nadDED;
    private $_nadLED;
    private $_equipment;
    private $_fullEmpty;

    private $_damages;

    private $_costTotals;
    private $_totalMessageAmounts;

    /*
     * messageID needs to be the estimate reference number
     */
    public function __construct($estimateReference = null, $identifier = 'WESTIM', $version = '0', $release = null, $controllingAgency = null, $association = null) {
        parent::__construct($identifier, $version, $release, $controllingAgency, $estimateReference, $association);

        $this->_estimateReference = $estimateReference;

        $this->_damages = [];
    }

    /*
     * $day = YYMMDD (used also in RFF+EST)
     */
    public function setTransactionDate($day, $time = null) {
        $this->_day = $day;
        $dt = $day;
        if ($time !== null) {
            $dt = [$day, $time];
        }
        $this->_dtmATR = ['DTM', 'ATR', $dt];
        return $this;
    }

    /*
     * $currency = XXX (three letter code)
     */
    public function setCurrency($currency) {
        $this->_currency = ['ACA', $currency, ['STD', 0]];
        return $this;
    }

    /*
     * $labourRate = \d+.\d{2}
     */
    public function setLabourRate($labourRate) {
        $this->_labourRate = ['LBR', $labourRate];
        return $this;
    }

    /*
     * Can be equal to the sender and receiver ID in UNH
     */
    public function setPartners($from, $to) {
        $this->_nadDED = ['NAD', 'DED', $from];
        $this->_nadLED = ['NAD', 'LED', $to];
        return $this;
    }

    /*
     * Container number separated between letters and numbers
     */
    public function setContainer($ownerCode, $serial, $isoSize, $maximumGrossWeight = 0) {
        $this->_equipment = ['EQF', 'CON', [$ownerCode, $serial], $isoSize, ['MGW', $maximumGrossWeight, 'KGM']];
        return $this;
    }

    /*
     * Full or Empty
     */
    public function setFullEmpty($fullEmpty) {
        $this->_fullEmpty = ['CUI', '', '', 'E'];
        return $this;
    }

    /*
     * Full or Empty
     */
    public function addDamage(Westim\Damage $damage) {
        $this->_damages[] = $damage;
        return $this;
    }

    /*
     *
     */
    public function setCostTotals($responsibility, $labour, $material, $handling, $tax, $invoiceAmount) {
        $this->_costTotals = ['CTO', $responsibility, $labour, $material, $handling, $tax, $invoiceAmount];
        return $this;
    }

    /*
     *
     */
    public function setTotalMessageAmounts($grandTotal, $authorizedAmount = null, $taxRate = null) {
        $this->_totalMessageAmounts = ['TMA', $grandTotal];
        if ($authorizedAmount !== null) {
            $this->_totalMessageAmounts[] = ['TMA', $grandTotal, '', '', '', '', $authorizedAmount];
        }
        if ($taxRate !== null) {
            $this->_totalMessageAmounts[] = ['TMA', $grandTotal, '', '', '', '', $authorizedAmount, '', $taxRate];
        }
        return $this;
    }

    public function compose($msgStatus = null) {
        $this->messageContent = [];

        $this->messageContent[] = $this->_dtmATR;
        $this->messageContent[] = ['RFF', 'EST', $this->_estimateReference, $this->_day];
        $this->messageContent[] = $this->_currency;
        $this->messageContent[] = $this->_labourRate;
        $this->messageContent[] = $this->_nadLED;
        $this->messageContent[] = $this->_nadDED;
        $this->messageContent[] = $this->_equipment;
        if ($this->_fullEmpty !== null) {
            $this->messageContent[] = $this->_fullEmpty;
        }
        $this->messageContent[] = ['ECI', 'D'];

        foreach ($this->_damages as $damage) {
            $content = $damage->compose();
            foreach ($content as $segment) {
                $this->messageContent[] = $segment;
            }
        }

        $this->messageContent[] = $this->_costTotals;
        $this->messageContent[] = $this->_totalMessageAmounts;

        parent::compose();
        return $this;
    }
}
