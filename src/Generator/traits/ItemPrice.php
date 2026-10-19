<?php

namespace EDI\Generator\Traits;

use EDI\Generator\EdiFactNumber;


/**
 * Gross and net price of a line item (PRI segments). Used by Ordrsp\Item together
 * with the Item trait, which composes the prices.
 *
 * @package EDI\Generator\Traits
 */
trait ItemPrice {

    /** @var array|null PRI segment, qualifier GRP (gross price) */
    protected $grossPrice;
    /** @var array|null PRI segment, qualifier NTP (net price) */
    protected $netPrice;

    /**
     * Builds a PRI segment.
     *
     * @param string           $qualifier     price qualifier, e.g. GRP (gross) or NTP (net)
     * @param string|int|float $value         price, formatted with 2 decimals and a decimal comma
     * @param int              $priceBase     quantity the price refers to
     * @param string           $priceBaseUnit measure unit of the price base, e.g. PCE
     *
     * @return array
     */
    public static function addPRISegment($qualifier, $value, $priceBase = 1, $priceBaseUnit = 'PCE') {
        return [
            'PRI',
            [
                $qualifier,
                EdiFactNumber::convert($value),
                '',
                '',
                $priceBase,
                $priceBaseUnit
            ]
        ];
    }

    /**
     * Returns the gross price (PRI segment, GRP), or null if not set.
     *
     * @return array|null
     */
    public function getGrossPrice() {
        return $this->grossPrice;
    }

    /**
     * Sets the gross price per piece (PRI segment, GRP).
     *
     * @param string|int|float $grossPrice gross price
     *
     * @return $this
     */
    public function setGrossPrice($grossPrice) {
        $this->grossPrice = self::addPRISegment('GRP', $grossPrice);
        return $this;
    }

    /**
     * Returns the net price (PRI segment, NTP), or null if not set.
     *
     * @return array|null
     */
    public function getNetPrice() {
        return $this->netPrice;
    }

    /**
     * Sets the net price per piece (PRI segment, NTP).
     *
     * @param string|int|float $netPrice net price
     *
     * @return $this
     */
    public function setNetPrice($netPrice) {
        $this->netPrice = self::addPRISegment('NTP', $netPrice);
        return $this;
    }
}
