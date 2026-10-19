<?php

namespace EDI\Generator\Traits;

use EDI\Generator\Base;
use EDI\Generator\EdifactDate;
use EDI\Generator\EdiFactNumber;
use EDI\Generator\Message;


/**
 * Line item of a commerce message: LIN segment with quantity (QTY), texts (IMD),
 * order and delivery note references (RFF) and dates (DTM).
 * Used by Desadv\Item, Invoic\Item, Orders\Item and Ordrsp\Item.
 *
 * @package EDI\Generator\Traits
 * @mixin Base
 */
trait Item {

  /** @var array|null LIN segment with line number and article number */
  protected $position;

  /** @var array|null QTY segment, qualifier 12 */
  protected $quantity;

  /** @var array|null DTM segment with the delivery date */
  protected $deliveryDate;

  /** @var array|null RFF segment, qualifier VN (order number of the wholesaler) */
  protected $orderNumberWholesaler;

  /** @var array|null DTM segment, qualifier 4 (order date) */
  protected $orderDate;

  /** @var array|null RFF segment, qualifier LI (order line number) */
  protected $orderPosition;

  /** @var array|null RFF segment AAJ, or a list of RFF AAJ and DTM when a delivery date was given */
  protected $deliveryNoteNumber;

  /** @var array|null DTM segment with the delivery note date */
  protected $deliveryNoteDate;

  /** @var array|null RFF segment, qualifier FI (delivery note line number) */
  protected $deliveryNotePosition;

  /** @var array[]|null IMD segments, type ZU (additional text) */
  protected $additionalText;

  /** @var array[]|null IMD segments, type SP (specification) */
  protected $specificationText;

  /** @var array[]|null IMD segments, type ZU; not in $composeKeys, so never sent */
  protected $generatedText;

  /** @var array[]|null IMD segments, type ZU; not in $composeKeys, so never sent */
  protected $featuresText;

  /** @var string[] property names composed in this order; keys without a property in the using class are skipped */
  protected $composeKeys
  = [
    'position',
    'specificationText',
    'additionalText',
    'quantity',
    'deliveryDate',
    'invoiceDescription',
    'netPrice',
    'grossPrice',
    'orderNumberWholesaler',
    'orderDate',
    'orderPosition',
    'deliveryNoteNumber',
    'deliveryNoteDate',
    'deliveryNotePosition',
    'discount',
    'discountFactor',
  ];

  /**
   * Returns the item segments in compose order.
   *
   * @return array list of segments
   * @throws \EDI\Generator\EdifactException if a set property holds no segment
   */
  public function compose() {
    $this->messageContent = [];

    return $this->composeByKeys($this->composeKeys);
  }

  /**
   * Returns the LIN segment, or null if not set.
   *
   * @return array|null
   */
  public function getPosition() {
    return $this->position;
  }

  /**
   * Sets line number and article number (LIN segment).
   *
   * @param string|int $position      line number
   * @param string     $articleNumber article number
   * @param string     $numberType    item number type, e.g. MF (manufacturer article number) or EN (EAN)
   *
   * @return $this
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
   * Returns the quantity (QTY segment), or null if not set.
   *
   * @return array|null
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Sets the quantity (QTY segment, qualifier 12). Whole numbers are sent as integers,
   * fractions with 3 decimals and a decimal comma.
   *
   * @param string|int|float $quantity quantity
   * @param string           $unit     measure unit: CMK, CMQ, CMT, DZN, GRM, HLT, KGM, KTM, LTR, MMT, MTK, MTQ, MTR, NRL, PCE, PR, SET, TNE
   *
   * @return $this
   * @throws \EDI\Generator\EdifactException if the unit is not allowed
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
        // whole numbers stay integers (QTY+12:5:PCE), fractions keep 3 decimals (QTY+12:2,500:KGM)
        is_numeric($quantity) && floor($quantity) == $quantity ? intval($quantity) : EdiFactNumber::convert($quantity, 3),
        $unit,
      ],
    ];

    return $this;
  }


  /**
   * Builds an IMD segment (item description). The text is split on word boundaries
   * into at most two components of 35 characters; the rest is dropped.
   *
   * @param string $description  description text
   * @param string $type         description type, e.g. ZU or SP
   * @param string $organization code list responsible agency, usually empty
   *
   * @return array IMD segment
   */
  public static function addIMDSegment($description, $type = 'ZU', $organization = '') {
    $components = Message::splitTextOnWordBoundary(trim((string) $description), 35, 2);
    $data =  [
      'IMD',
      '',
      '',
      [
        $type,
        '',
        $organization,
        $components[0] ?? '',
      ],
    ];
    if (isset($components[1])) {
      $data[3][] = $components[1];
    }

    return $data;
  }

  /**
   * Adds an additional text as IMD segments (type ZU): up to 320 characters in lines of 40.
   * Empty texts are ignored; further calls append.
   *
   * @param string|null $text text
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
   * Returns the IMD segments of the additional text, or null if not set.
   *
   * @return array[]|null
   */
  public function getAdditionalText() {
    return $this->additionalText;
  }


  /**
   * Adds a specification text as IMD segments (type SP): up to 80 characters in lines of 40.
   * Further calls append.
   *
   * @param string|null $text text
   *
   * @return $this
   */
  public function setSpecificationText($text) {
    $this->splitTexts('specificationText', $text, 80, 40, 'SP');

    return $this;
  }

  /**
   * Returns the IMD segments of the specification text, or null if not set.
   *
   * @return array[]|null
   */
  public function getSpecificationText() {
    return $this->specificationText;
  }


  /**
   * Stores a generated text as IMD segments (type ZU): up to 70 characters in lines of 35.
   * The text is not part of $composeKeys and is therefore not sent.
   *
   * @param string|null $text text
   *
   * @return $this
   */
  public function setGeneratedText($text) {
    $this->splitTexts('generatedText', $text, 70, 35);

    return $this;
  }

  /**
   * Returns the IMD segments of the generated text, or null if not set.
   *
   * @return array[]|null
   */
  public function getGeneratedText() {
    return $this->generatedText;
  }


  /**
   * Stores a features text as IMD segments (type ZU): up to 70 characters in lines of 35.
   * The text is not part of $composeKeys and is therefore not sent. Does not return $this.
   *
   * @param string|null $text text
   *
   * @return void
   */
  public function setFeaturesText($text) {
    $this->splitTexts('featuresText', $text, 70, 35);
  }

  /**
   * Cleans a text, splits it into lines and appends one IMD segment per line to a property.
   *
   * @param string      $varName    name of the property that collects the IMD segments
   * @param string|null $text       text
   * @param int         $maxLength  maximum number of characters kept
   * @param int         $lineLength maximum characters per line
   * @param string      $type       IMD description type
   *
   * @return $this
   */
  private function splitTexts($varName, $text, $maxLength, $lineLength, $type = 'ZU') {
    $data = Message::splitTextOnWordBoundary(
      $this->clearUTF8chars($text),
      $lineLength,
      (int) floor($maxLength / max($lineLength, 1))
    );
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
   * Transliterates German umlauts and ß, then removes every character that is not printable ASCII.
   *
   * @param string|null $string text
   *
   * @return string|null cleaned text, null on a regex error
   */
  public function clearUTF8chars($string) {
    // transliterate German letters first, the regex below drops every non-ASCII byte
    $string = strtr((string) $string, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss']);
    return preg_replace('/[[:^print:]]/', '', ($string));
    // return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', ($string));
  }


  /**
   * Returns the order number of the wholesaler (RFF segment, VN), or null if not set.
   *
   * @return array|null
   */
  public function getOrderNumberWholesaler() {
    return $this->orderNumberWholesaler;
  }

  /**
   * Sets the order number of the wholesaler (RFF segment, qualifier VN); omitted when empty.
   *
   * @param string $orderNumberWholesaler order number
   *
   * @return $this
   */
  public function setOrderNumberWholesaler($orderNumberWholesaler) {
    $this->orderNumberWholesaler = $this->addRFFSegment('VN', $orderNumberWholesaler);

    return $this;
  }

  /**
   * Returns the order date (DTM segment, qualifier 4), or null if not set.
   *
   * @return array|null
   */
  public function getOrderDate() {
    return $this->orderDate;
  }

  /**
   * Sets the order date (DTM segment, qualifier 4).
   *
   * @param string|\DateTimeInterface $orderDate date as 'Y-m-d' string or date object
   *
   * @return $this
   * @throws \EDI\Generator\EdifactException if the date is invalid
   */
  public function setOrderDate($orderDate) {
    $this->orderDate = $this->addDTMSegment($orderDate, '4');

    return $this;
  }

  /**
   * Returns the order line number (RFF segment, LI), or null if not set.
   *
   * @return array|null
   */
  public function getOrderPosition() {
    return $this->orderPosition;
  }

  /**
   * Sets the order line number (RFF segment, qualifier LI); omitted when empty.
   *
   * @param string|int $orderPosition line number in the order
   *
   * @return $this
   */
  public function setOrderPosition($orderPosition) {
    $this->orderPosition = $this->addRFFSegment('LI', $orderPosition);

    return $this;
  }

  /**
   * Returns the delivery note reference (RFF AAJ, with DTM when a date was given), or null if not set.
   *
   * @return array|null
   */
  public function getDeliveryNoteNumber() {
    return $this->deliveryNoteNumber;
  }

  /**
   * Sets the delivery note number (RFF segment, qualifier AAJ) and an optional delivery
   * date (DTM segment, qualifier 2).
   *
   * @param string                         $deliveryNoteNumber delivery note number
   * @param string|\DateTimeInterface|null $deliveryDate       delivery date, omitted when empty
   *
   * @return $this
   * @throws \EDI\Generator\EdifactException if the date is invalid
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
   * Sets the delivery date of the item (DTM segment).
   *
   * @param string|\DateTimeInterface $deliveryDate date, format depends on $format
   * @param int                       $type         date qualifier, default 2 (requested delivery date)
   * @param int                       $format       EdifactDate::DATE (102) or EdifactDate::DATETIME (203)
   *
   * @return $this
   * @throws \EDI\Generator\EdifactException if the date is invalid
   */
  public function setDeliveryDate($deliveryDate, $type = EdifactDate::TYPE_DELIVERY_DATE_REQUESTED, $format = EdifactDate::DATE) {
    $this->deliveryDate = $this->addDTMSegment($deliveryDate, $type, $format);

    return $this;
  }

  /**
   * Returns the delivery note date (DTM segment), or null if not set.
   *
   * @return array|null
   */
  public function getDeliveryNoteDate() {
    return $this->deliveryNoteDate;
  }

  /**
   * Sets the delivery note date (DTM segment).
   *
   * @param string|\DateTimeInterface $deliveryNoteDate date, format depends on $formatQuantifier
   * @param int                       $type             date qualifier, default 2 (requested delivery date)
   * @param int                       $formatQuantifier EdifactDate::DATE (102) or EdifactDate::DATETIME (203)
   *
   * @return $this
   * @throws \EDI\Generator\EdifactException if the date is invalid
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
   * Returns the delivery note line number (RFF segment, FI), or null if not set.
   *
   * @return array|null
   */
  public function getDeliveryNotePosition() {
    return $this->deliveryNotePosition;
  }

  /**
   * Sets the delivery note line number (RFF segment, qualifier FI); omitted when empty.
   *
   * @param string|int $deliveryNotePosition line number on the delivery note
   *
   * @return $this
   */
  public function setDeliveryNotePosition($deliveryNotePosition) {
    $this->deliveryNotePosition = $this->addRFFSegment('FI', $deliveryNotePosition);

    return $this;
  }
}
