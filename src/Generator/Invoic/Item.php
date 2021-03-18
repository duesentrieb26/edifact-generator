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
use EDI\Generator\EdiFactNumber;
use EDI\Generator\Message;

/**
 * Class Item
 *
 * @package EDI\Generator\Invoic
 */
class Item extends Base
{
  const DISCOUNT_TYPE_PERCENT = 'percent';
  const DISCOUNT_TYPE_ABSOLUTE = 'absolute';

  use \EDI\Generator\Traits\Item;

  /** @var array */
  protected $invoiceDescription;
  /** @var array */
  protected $grossPrice;
  /** @var array */
  protected $netPrice;
  /** @var int */
  protected $discountIndex = 0;

  /** @var array */
  protected $productInformation;

  /** @var array */
  protected $deliveryDate;

  /**
   * @return array
   */
  public function getInvoiceDescription()
  {
    return $this->invoiceDescription;
  }

  /**
   * @param string $invoiceDescription
   *
   * @return Item
   */
  public function setInvoiceDescription($invoiceDescription)
  {
    $this->invoiceDescription = Message::addFTXSegment($invoiceDescription, 'INV');
    $this->addKeyToCompose('invoiceDescription', $this->composeKeys);
    return $this;
  }


  /**
   * @param        $qualifier
   * @param        $value
   * @param int    $priceBase
   * @param string $priceBaseUnit
   *
   * @return array
   */
  public static function addPRISegment($qualifier, $value, $priceBase = 1, $priceBaseUnit = 'PCE')
  {
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
   * @return array
   */
  public function getGrossPrice()
  {
    return $this->grossPrice;
  }

  /**
   * @param string $grossPrice
   *
   * @return Item
   */
  public function setGrossPrice($grossPrice)
  {
    $this->grossPrice = self::addPRISegment('GRP', $grossPrice);
    $this->addKeyToCompose('grossPrice');
    return $this;
  }

  /**
   * @return array
   */
  public function getNetPrice()
  {
    return $this->netPrice;
  }

  /**
   * @param string $netPrice
   *
   * @return Item
   */
  public function setNetPrice($netPrice)
  {
    $this->netPrice = self::addPRISegment('NTP', $netPrice);
//    $this->addKeyToCompose('netPrice', $this->composeKeys, 'orderPosition');

    return $this;
  }

  /**
   * @param float  $value
   * @param float  $total
   * @param string $discountType
   *
   * @return Item
   */
  public function addDiscount($value, $total, $discountType = self::DISCOUNT_TYPE_PERCENT)
  {
    $index = 'discount' . $this->discountIndex++;
    $this->{$index} = [
      'ALC',
      floatval($value) > 0 ? 'C' : 'A',
      '',
      '',
      '',
      'SF',
    ];
    $this->addKeyToCompose($index);

    $index = 'discount' . $this->discountIndex++;
    if ($discountType == self::DISCOUNT_TYPE_PERCENT) {
      $discount = $total * (abs($value) / 100);
    } else {
      $discount = abs($value);
    }
    $this->{$index} = [
      'PCD',
      [
        '3',
        EdiFactNumber::convert($discount),
      ],
    ];
    $this->addKeyToCompose($index);

    $index = 'discount' . $this->discountIndex++;
    $this->{$index} = self::addMOASegment('8', abs($total));
    $this->addKeyToCompose($index);


    return $this;
  }


  /**
   * @param     $total
   * @param int $segment
   */
  public function setTotal($total, $segment = 8)
  {
    $index = 'discount' . $this->discountIndex++;
    $this->{$index} = self::addMOASegment($segment, $total);
    $this->addKeyToCompose($index);
  }


  /**
   * EAN Nummer
   *
   * @param $ean
   *
   * @return self
   */
  public function addProductInformation($ean)
  {
    $this->productInformation = self::addPIASegment($ean);
    $this->addKeyToCompose('productInformation');

    return $this;
  }

  /**
   * @param     $deliveryDate
   * @param int $type
   * @param int $formatQuantifier
   *
   * @return $this
   * @throws \EDI\Generator\EdifactException
   */
  public function setDeliveryDate($deliveryDate, $type = EdifactDate::TYPE_DELIVERY_DATE_ACTUAL,
    $formatQuantifier = EdifactDate::DATE
  ) {
    $this->deliveryDate = $this->addDTMSegment($deliveryDate, $type, $formatQuantifier);

    return $this;
  }

}
