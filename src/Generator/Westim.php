<?php

namespace EDI\Generator;

/**
 * WESTIM message (container damage and repair estimate), version 0 without release or agency.
 *
 * A depot sends a repair estimate for one container with its damages, work and costs.
 *
 * @package EDI\Generator
 */
class Westim extends Message {
    /** @var string|null transaction date as YYMMDD, also used in RFF+EST */
    private $_day;
    /** @var string|null estimate reference number, also the message reference */
    private $_estimateReference;

    /** @var array|null DTM+ATR segment, transaction date and time */
    private $_dtmATR;
    /** @var array|null ACA segment, currency */
    private $_currency;
    /** @var array|null LBR segment, labour rate */
    private $_labourRate;
    /** @var array|null NAD+DED segment, sender */
    private $_nadDED;
    /** @var array|null NAD+LED segment, receiver */
    private $_nadLED;
    /** @var array|null EQF+CON segment, container */
    private $_equipment;
    /** @var array|null CUI segment, full/empty indicator */
    private $_fullEmpty;

    /** @var Westim\Damage[] damages with their repair work and cost */
    private $_damages;

    /** @var array|null CTO segment, cost totals */
    private $_costTotals;
    /** @var array|null TMA segment, total message amounts */
    private $_totalMessageAmounts;

    /**
     * Creates the estimate; the estimate reference is also used as message reference.
     *
     * @param string|null $estimateReference estimate reference number
     * @param string      $identifier        message type
     * @param string      $version           message version
     * @param string|null $release           message release
     * @param string|null $controllingAgency controlling agency
     * @param string|null $association       association assigned code
     */
    public function __construct($estimateReference = null, $identifier = 'WESTIM', $version = '0', $release = null, $controllingAgency = null, $association = null) {
        parent::__construct($identifier, $version, $release, $controllingAgency, $estimateReference, $association);

        $this->_estimateReference = $estimateReference;

        $this->_damages = [];
    }

    /**
     * Sets the transaction date and optional time (DTM+ATR segment).
     *
     * @param string      $day  date as YYMMDD, also used in RFF+EST
     * @param string|null $time time as HHMM
     *
     * @return $this
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

    /**
     * Sets the currency (ACA segment).
     *
     * @param string $currency three-letter ISO 4217 currency code
     *
     * @return $this
     */
    public function setCurrency($currency) {
        $this->_currency = ['ACA', $currency, ['STD', 0]];
        return $this;
    }

    /**
     * Sets the labour rate (LBR segment).
     *
     * @param string $labourRate labour rate with two decimals, e.g. 25.00
     *
     * @return $this
     */
    public function setLabourRate($labourRate) {
        $this->_labourRate = ['LBR', $labourRate];
        return $this;
    }

    /**
     * Sets sender (NAD+DED segment) and receiver (NAD+LED segment); they can equal the IDs in UNB.
     *
     * @param string $from sender identification
     * @param string $to   receiver identification
     *
     * @return $this
     */
    public function setPartners($from, $to) {
        $this->_nadDED = ['NAD', 'DED', $from];
        $this->_nadLED = ['NAD', 'LED', $to];
        return $this;
    }

    /**
     * Sets the container (EQF+CON segment); the container number is split into owner code and serial.
     *
     * @param string     $ownerCode          owner code, the letters of the container number
     * @param string     $serial             serial number, the digits of the container number
     * @param string     $isoSize            ISO size and type code, e.g. 22G1
     * @param int|string $maximumGrossWeight maximum gross weight in kilograms
     *
     * @return $this
     */
    public function setContainer($ownerCode, $serial, $isoSize, $maximumGrossWeight = 0) {
        $this->_equipment = ['EQF', 'CON', [$ownerCode, $serial], $isoSize, ['MGW', $maximumGrossWeight, 'KGM']];
        return $this;
    }

    /**
     * Sets whether the container is full or empty (CUI segment).
     *
     * @param string $fullEmpty F = full, E = empty
     *
     * @return $this
     */
    public function setFullEmpty($fullEmpty) {
        $this->_fullEmpty = ['CUI', '', '', $fullEmpty];
        return $this;
    }

    /**
     * Adds a damage with its repair work and cost.
     *
     * @param Westim\Damage $damage damage (DAM, WOR, COS segments)
     *
     * @return $this
     */
    public function addDamage(Westim\Damage $damage) {
        $this->_damages[] = $damage;
        return $this;
    }

    /**
     * Sets the cost totals of the estimate (CTO segment).
     *
     * @param string           $responsibility responsibility code, who pays
     * @param string|int|float $labour         labour cost
     * @param string|int|float $material       material cost
     * @param string|int|float $handling       handling cost
     * @param string|int|float $tax            tax amount
     * @param string|int|float $invoiceAmount  invoice amount
     *
     * @return $this
     */
    public function setCostTotals($responsibility, $labour, $material, $handling, $tax, $invoiceAmount) {
        $this->_costTotals = ['CTO', $responsibility, $labour, $material, $handling, $tax, $invoiceAmount];
        return $this;
    }

    /**
     * Sets the total amounts of the estimate (TMA segment); empty trailing elements are left out.
     *
     * @param string|int|float      $grandTotal       grand total, element 1
     * @param string|int|float|null $authorizedAmount authorized amount, element 6
     * @param string|int|float|null $taxRate          tax rate, element 8
     *
     * @return $this
     */
    public function setTotalMessageAmounts($grandTotal, $authorizedAmount = null, $taxRate = null) {
        $tma = ['TMA', $grandTotal, '', '', '', '', $authorizedAmount ?? '', '', $taxRate ?? ''];
        while (end($tma) === '') {
            array_pop($tma);
        }
        $this->_totalMessageAmounts = $tma;
        return $this;
    }

    /**
     * Builds the message segments (DTM, RFF+EST, ACA, LBR, NAD, EQF, CUI, ECI, the damages, CTO, TMA)
     * and wraps them in UNH/UNT.
     *
     * @param null $msgStatus unused, kept for compatibility with Message::compose()
     *
     * @return $this
     * @throws EdifactException when the message has no content
     */
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
