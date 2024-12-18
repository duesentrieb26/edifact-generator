<?php

namespace EDI\Generator\Traits;

use EDI\Generator\Base;
use EDI\Generator\EdifactDate;


/**
 * Trait Item
 *
 * @package EDI\Generator\Traits
 * @mixin Base
 */
trait Item {

  /** @var array */
  protected $position;

  /** @var array */
  protected $quantity;

  /** @var array */
  protected $orderNumberWholesaler;

  /** @var array */
  protected $orderDate;

  /** @var array */
  protected $orderPosition;

  /** @var array */
  protected $deliveryNoteNumber;

  /** @var array */
  protected $deliveryNoteDate;

  /** @var array */
  protected $deliveryNotePosition;

  /** @var array IMD ZU */
  protected $additionalText;

  /** @var array IMD SP */
  protected $specificationText;

  /** @var array IMD GAT */
  protected $generatedText;

  /** @var array IMD M */
  protected $featuresText;

  /** @var array */
  protected $composeKeys
  = [
    'position',
    'specificationText',
    'additionalText',
    'quantity',
    'deliveryNoteDate',
    'deliveryDate',
    'invoiceDescription',
    'grossPrice',
    'netPrice',
    'orderNumberWholesaler',
    'orderDate',
    'orderPosition',
    'deliveryNoteNumber',
    'deliveryNotePosition',
    'discount',
    'discountFactor',
  ];

  /**
   * @return array
   * @throws \EDI\Generator\EdifactException
   */
  public function compose() {
    return $this->composeByKeys($this->composeKeys);
  }

  /**
   * @return array
   */
  public function getPosition() {
    return $this->position;
  }

  /**
   * @param string $position
   * @param string $articleNumber
   * @param string $numberType
   *
   * @return Item
   */
  public function setPosition($position, $articleNumber, $numberType = 'MF') {
    $this->position = [
      'LIN',
      $position,
      '',
      [
        $articleNumber,
        $numberType,
      ],
    ];

    return $this;
  }

  /**
   * @return array
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * @param string $quantity
   * @param        $unit
   *
   * @return Item
   * @throws \EDI\Generator\EdifactException
   */
  public function setQuantity($quantity, $unit = 'PCE') {
    $this->isAllowed(
      $unit,
      [
        'CMK',
        'CMQ',
        'CMT',
        'DZN',
        'GRM',
        'HLT',
        'KGM',
        'KTM',
        'LTR',
        'MMT',
        'MTK',
        'MTQ',
        'MTR',
        'NRL',
        'PCE',
        'PR',
        'SET',
        'TNE',
      ]
    );

    $this->quantity = [
      'QTY',
      [
        '12',
        intval($quantity),
        //        EdiFactNumber::convert($quantity, 3, EdiFactNumber::DECIMAL_POINT),
        $unit,
      ],
    ];

    return $this;
  }


  /**
   * @param        $description
   * @param string $type
   * @param string $organization
   *
   * @return array
   */
  public static function addIMDSegment($description, $type = 'ZU', $organization = '') {
    $data =  [
      'IMD',
      '',
      '',
      [
        $type,
        '',
        $organization,
        substr(trim($description), 0, 35),
      ],
    ];

    if (strlen($description) > 35) {
      $data[3][] = substr(trim($description), 35, 35);
    }

    return $data;
  }

  /**
   * @param $text
   *
   * @return $this
   */
  public function setAdditionalText($text) {
    if (!empty($text)) {
      $this->splitTexts('additionalText', $text, 320, 40, 'ZU');
    }

    return $this;
  }

  /**
   * @return array
   */
  public function getAdditionalText() {
    return $this->additionalText;
  }


  /**
   * @param             $text
   *
   * @return $this
   */
  public function setSpecificationText($text) {
    $this->splitTexts('specificationText', $text, 80, 40, 'SP');

    return $this;
  }

  /**
   * @return array
   */
  public function getSpecificationText() {
    return $this->specificationText;
  }


  /**
   * @param $text
   *
   * @return $this
   */
  public function setGeneratedText($text) {
    $this->splitTexts('generatedText', $text, 70, 35);

    return $this;
  }

  /**
   * @return array
   */
  public function getGeneratedText() {
    return $this->generatedText;
  }


  /**
   * @param $text
   */
  public function setFeaturesText($text) {
    $this->splitTexts('featuresText', $text, 70, 35);
  }

  /**
   * @param string  $varName
   * @param string  $text
   * @param integer $maxLength
   * @param integer $lineLength
   * @param string  $type
   *
   * @return $this
   */
  private function splitTexts($varName, $text, $maxLength, $lineLength, $type = 'ZU') {
    $data = str_split(mb_substr($this->clearUTF8chars($text), 0, $maxLength), $lineLength);
    $prop = &$this->{$varName};
    foreach ($data as $line) {
      $segmentData = self::addIMDSegment($line, $type);
      if (is_array($prop) && $prop[0][0] == $segmentData[0]) {
        array_push($prop, $segmentData);
      }

      if (is_null($prop)) {
        $prop = [$segmentData];
      }
    }

    return $this;
  }

  /**
   * clear all not printable characters from given string
   * @param string  $string
   */
  public function clearUTF8chars($string) {
    return preg_replace('/[[:^print:]]/', '', ($string));
    // return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', ($string));
  }


  /**
   * @return array
   */
  public function getOrderNumberWholesaler() {
    return $this->orderNumberWholesaler;
  }

  /**
   * @param string $orderNumberWholesaler
   *
   * @return Item
   */
  public function setOrderNumberWholesaler($orderNumberWholesaler) {
    $this->orderNumberWholesaler = $this->addRFFSegment('VN', $orderNumberWholesaler);

    return $this;
  }

  /**
   * @return array
   */
  public function getOrderDate() {
    return $this->orderDate;
  }

  /**
   * @param string $orderDate
   *
   * @return Item
   * @throws \EDI\Generator\EdifactException
   */
  public function setOrderDate($orderDate) {
    $this->orderDate = $this->addDTMSegment($orderDate, '4');

    return $this;
  }

  /**
   * @return array
   */
  public function getOrderPosition() {
    return $this->orderPosition;
  }

  /**
   * @param string $orderPosition
   *
   * @return Item
   */
  public function setOrderPosition($orderPosition) {
    $this->orderPosition = $this->addRFFSegment('LI', $orderPosition);

    return $this;
  }

  /**
   * @return array
   */
  public function getDeliveryNoteNumber() {
    return $this->deliveryNoteNumber;
  }

  /**
   * @param string      $deliveryNoteNumber
   * @param string|null $deliveryDate
   *
   * @return Item
   * @throws \EDI\Generator\EdifactException
   */
  public function setDeliveryNoteNumber($deliveryNoteNumber, $deliveryDate = null) {
    $data = $this->addRFFSegment('AAJ', $deliveryNoteNumber);
    if ($deliveryDate) {
      $data = [
        $this->addRFFSegment('AAJ', $deliveryNoteNumber)
      ];
      array_push(
        $data,
        $this->addDTMSegment(
          $deliveryDate,
          EdifactDate::TYPE_DELIVERY_DATE_REQUESTED,
          EdifactDate::DATE
        )
      );
    }
    $this->deliveryNoteNumber = $data;

    return $this;
  }

  /**
   * @return array
   */
  public function getDeliveryNoteDate() {
    return $this->deliveryNoteDate;
  }

  /**
   * @param string|\DateTime $deliveryNoteDate
   * @param int              $type
   * @param int              $formatQuantifier
   *
   * @return Item
   */
  public function setDeliveryNoteDate(
    $deliveryNoteDate,
    $type = EdifactDate::TYPE_DELIVERY_DATE_REQUESTED,
    $formatQuantifier = EdifactDate::DATE
  ) {
    $this->deliveryNoteDate = $this->addDTMSegment($deliveryNoteDate, $type, $formatQuantifier);

    return $this;
  }

  /**
   * @return array
   */
  public function getDeliveryNotePosition() {
    return $this->deliveryNotePosition;
  }

  /**
   * @param string|integer $deliveryNotePosition
   *
   * @return Item
   */
  public function setDeliveryNotePosition($deliveryNotePosition) {
    $this->deliveryNotePosition = $this->addRFFSegment('FI', $deliveryNotePosition);

    return $this;
  }
}
