<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 08.02.2018
 * Time: 14:11
 */

namespace EDI\Generator\Invoic;


use EDI\Generator\Base;
use EDI\Generator\EdifactDate;
use EDI\Generator\EdifactException;
use EDI\Generator\EdiFactNumber;
use EDI\Generator\Message;

/**
 * One line item of an INVOIC message: LIN with quantity, texts and references from the
 * Item trait, plus prices (PRI), allowances and charges (ALC/PCD/MOA) and product information (PIA).
 *
 * @package EDI\Generator\Invoic
 */
class Item extends Base {
  /** Discount given as a percentage of the value before discount. */
  const DISCOUNT_TYPE_PERCENT = 'percent';
  /** Discount given as an absolute amount. */
  const DISCOUNT_TYPE_ABSOLUTE = 'absolute';

  use \EDI\Generator\Traits\Item;

  /** @var array|null FTX segment, item description (qualifier INV) */
  protected $invoiceDescription;
  /** @var array|null PRI segment, gross price (qualifier GRP) */
  protected $grossPrice;
  /** @var array|null PRI segment, net price (qualifier NTP) */
  protected $netPrice;

  /** @var array[]|null ALC, PCD and MOA segments of all discounts and surcharges */
  protected $discount;

  /** @var array[]|null ALC, PCD and MOA segments of the overall discount factor */
  protected $discountFactor;

  /** @var array[]|null MOA segments added with setTotal() */
  protected $totals;

  /** @var array|null PIA segment with the EAN */
  protected $productInformation;

  /** @var array|null DTM segment, delivery date of the item */
  protected $deliveryDate;

  /**
   * Returns the item description (FTX segment).
   *
   * @return array|null
   */
  public function getInvoiceDescription() {
    return $this->invoiceDescription;
  }

  /**
   * Sets the item description (FTX segment, qualifier INV).
   *
   * @param string $invoiceDescription free text, split into lines of 70 characters
   *
   * @return $this
   */
  public function setInvoiceDescription($invoiceDescription) {
    $this->invoiceDescription = Message::addFTXSegment($invoiceDescription, 'INV');
    return $this;
  }


  /**
   * Builds a price segment (PRI).
   *
   * @param string           $qualifier     price qualifier, e.g. GRP (gross) or NTP (net)
   * @param string|int|float $value         price, "1.234,56" strings are accepted
   * @param int              $priceBase     quantity the price refers to
   * @param string           $priceBaseUnit unit of the price base quantity
   *
   * @return array PRI segment
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
        $priceBaseUnit,
      ],
    ];
  }

  /**
   * Returns the gross price (PRI segment, qualifier GRP).
   *
   * @return array|null
   */
  public function getGrossPrice() {
    return $this->grossPrice;
  }

  /**
   * Sets the gross price per piece (PRI segment, qualifier GRP).
   *
   * @param string|int|float $grossPrice price, "1.234,56" strings are accepted
   *
   * @return $this
   */
  public function setGrossPrice($grossPrice) {
    $this->grossPrice = self::addPRISegment('GRP', $grossPrice);
    return $this;
  }

  /**
   * Returns the net price (PRI segment, qualifier NTP).
   *
   * @return array|null
   */
  public function getNetPrice() {
    return $this->netPrice;
  }

  /**
   * Sets the net price per piece (PRI segment, qualifier NTP).
   *
   * @param string|int|float $netPrice price, "1.234,56" strings are accepted
   *
   * @return $this
   */
  public function setNetPrice($netPrice) {
    $this->netPrice = self::addPRISegment('NTP', $netPrice);

    return $this;
  }

  /**
   * Adds a discount or surcharge: ALC segment, PCD+3 with the percentage (percent type only)
   * and MOA+8 with the amount. Can be called several times.
   *
   * @param string|int|float $value               percentage or amount; positive for a surcharge (ALC+C),
   *                                              zero or negative for a discount (ALC+A)
   * @param string           $discountType        DISCOUNT_TYPE_PERCENT or DISCOUNT_TYPE_ABSOLUTE
   * @param string|int|float $valueBeforeDiscount amount the percentage is applied to (percent type only)
   * @param string           $discountText        optional description in the ALC segment
   * @param string           $serviceCode         special service code; default SF (percent) or DI (absolute)
   *
   * @return $this
   */
  public function addDiscount(
    $value,
    $discountType = self::DISCOUNT_TYPE_PERCENT,
    $valueBeforeDiscount = 0,
    $discountText = '',
    $serviceCode = ''
  ) {
    if (!is_array($this->discount)) {
      $this->discount = [];
    }

    $isPercent = $discountType == self::DISCOUNT_TYPE_PERCENT;
    if (!empty($serviceCode)) {
      $code = $serviceCode;
    } else {
      $code = $isPercent ? 'SF' : 'DI';
    }

    $c214 = [$code];
    if (!empty($discountText)) {
      $c214 = [$code, '', '', $discountText];
    }

    array_push(
      $this->discount,
      [
        'ALC',
        floatval($value) > 0 ? 'C' : 'A',
        '',
        '',
        '',
        $c214,
      ]
    );

    // an absolute discount has no percentage, its value is the amount itself
    if ($isPercent) {
      array_push(
        $this->discount,
        [
          'PCD',
          [
            '3',
            EdiFactNumber::convert(abs($value)),
          ],
        ]
      );
    }


    array_push(
      $this->discount,
      self::addMOASegment(
        '8',
        $isPercent ? $valueBeforeDiscount * (abs($value) / 100) : abs($value)
      )
    );

    return $this;
  }


  /**
   * Sets the overall discount factor of the item: ALC+SF (A for a discount, C for a surcharge),
   * PCD+1 with the factor (4 decimals) and MOA+8 with the difference. Replaces an earlier factor.
   *
   * @param string|int|float $valueAfterDiscount  value after all discounts
   * @param string|int|float $valueBeforeDiscount value before discounts; 0 sends no segments
   *
   * @return $this
   * @throws EdifactException if $valueBeforeDiscount is 0 but $valueAfterDiscount is greater than 0
   */
  public function addDiscountFactor($valueAfterDiscount, $valueBeforeDiscount) {
    $this->discountFactor = [];
    if ($valueBeforeDiscount == 0) {
      if ($valueAfterDiscount > 0) {
        throw new EdifactException('valueBeforeDiscount cannot be 0, if valueAfterDiscount > 0');
      }

      return $this;
    }
    $factor = $valueAfterDiscount / $valueBeforeDiscount;

    array_push(
      $this->discountFactor,
      [
        'ALC',
        floatval($factor) > 1 ? 'C' : 'A',
        '',
        '',
        '',
        [
          'SF',
        ],
      ]
    );

    array_push(
      $this->discountFactor,
      [
        'PCD',
        [
          '1',
          EdiFactNumber::convert($factor, 4),
        ],
      ]
    );

    array_push(
      $this->discountFactor,
      self::addMOASegment(
        '8',
        $valueBeforeDiscount - $valueAfterDiscount
      )
    );

    return $this;
  }


  /**
   * Adds a monetary amount (MOA segment) at the end of the item. Can be called several times.
   *
   * @param string|int|float $total   amount, "1.234,56" strings are accepted
   * @param string|int       $segment MOA qualifier, default 8 (allowance or charge amount)
   *
   * @return $this
   */
  public function setTotal($total, $segment = 8) {
    if (!is_array($this->totals)) {
      $this->totals = [];
      $this->addKeyToCompose('totals');
    }
    $this->totals[] = self::addMOASegment($segment, $total);

    return $this;
  }


  /**
   * Sets the EAN of the item (PIA segment), sent at the end of the item.
   *
   * @param string $ean EAN/GTIN
   *
   * @return $this
   */
  public function addProductInformation($ean) {
    $this->productInformation = self::addPIASegment($ean);
    $this->addKeyToCompose('productInformation');

    return $this;
  }

  /**
   * Sets the delivery date of the item (DTM segment).
   *
   * @param string|\DateTimeInterface $deliveryDate     date as "Y-m-d" string or date object
   * @param int                       $type             DTM qualifier, default 35 (actual delivery date)
   * @param int                       $formatQuantifier EdifactDate format code, default 102 (CCYYMMDD)
   *
   * @return $this
   * @throws \EDI\Generator\EdifactException if the date is invalid
   */
  public function setDeliveryDate(
    $deliveryDate,
    $type = EdifactDate::TYPE_DELIVERY_DATE_ACTUAL,
    $formatQuantifier = EdifactDate::DATE
  ) {
    $this->deliveryDate = $this->addDTMSegment($deliveryDate, $type, $formatQuantifier);

    return $this;
  }
}
