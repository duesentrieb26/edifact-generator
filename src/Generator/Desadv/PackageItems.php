<?php

namespace EDI\Generator\Desadv;


use EDI\Generator\Base;
use EDI\Generator\EdifactDate;
use EDI\Generator\EdifactException;

/**
 * DESADV item packed in a Package: LIN segment with quantity (QTY), optional
 * package type (PAC) and the order and delivery note references (RFF, DTM).
 *
 * @package EDI\Generator\Desadv
 */
class PackageItem extends Base {

  /** @var array|null unused, never set */
  protected $cps;

  /** @var array|null QTY segment with the despatched quantity */
  protected $quantity;

  /** @var array|null LIN segment with position and article number */
  protected $content;

  /** @var int|null unused, never set */
  protected $cpsMainCounter;

  /** @var int|null unused, never set */
  protected $cpsCounter;

  /** @var array|null unused, never set */
  protected $weight;

  /** @var array|null RFF segment, qualifier VN (order number) */
  protected $orderNumber;

  /** @var array|null RFF segment, qualifier LI (order line number) */
  protected $orderNumberPosition;

  /** @var array|null RFF AAJ, RFF FI and optional DTM segments of the delivery note */
  protected $deliveryNoteNumber;

  /** @var array|null unused, never set (the position is part of $deliveryNoteNumber) */
  protected $deliveryNotePosition;

  /** @var array|null PAC segment with the package type of this item */
  protected $pac;

  /** @var string[] property names composed in this order */
  protected $composeKeys = [
    'cps',
    'pac',
    'content',
    'quantity',
    'orderNumber',
    'orderNumberPosition',
    'deliveryNoteNumber',
    'deliveryNotePosition'
  ];



  /**
   * Creates an empty package item.
   */
  public function __construct() {
  }



  /**
   * Sets position, article number (LIN segment) and quantity (QTY segment) of the item.
   *
   * @param int        $position line number
   * @param string     $content  article number (EAN or manufacturer article number)
   * @param string     $type     article number type: EN (EAN) or MF (manufacturer)
   * @param int|string $quantity despatched quantity in PCE
   *
   * @return $this
   */
  public function setPackageContent($position, $content, $type, $quantity = 1) {
    $this->content = $this->setPosition($position, $content, $type);
    $this->quantity = $this->addQTYSegment($quantity);

    return $this;
  }


  /**
   * Sets the package type of the item (PAC segment).
   *
   * @param string $type     package type: BB, BG, BH, BK, CF, CG, CH, CT, PA, PC, PG, PN, PU, SC, TU
   * @param int    $quantity number of packages
   *
   * @return $this
   * @throws EdifactException if the package type is not allowed
   */
  public function setPackageType($type, $quantity = 1) {
    $allowedUnits = [
      'BB',
      'BG',
      'BH',
      'BK',
      'CF',
      'CG',
      'CH',
      'CT',
      'PA',
      'PC',
      'PG',
      'PN',
      'PU',
      'SC',
      'TU',
    ];
    if (!in_array($type, $allowedUnits)) {
      throw new EdifactException('Invalid package type ' . $type . '. Only these are allowed ' . implode(', ', $allowedUnits));
    }
    $this->pac = Package::addPACSegment($quantity, $type);

    return $this;
  }


  /**
   * Builds the LIN segment for this item. Despite its name it does not store
   * anything; it returns the segment.
   *
   * @param int    $position line number
   * @param string $content  article number (EAN or manufacturer article number)
   * @param string $type     article number type: EN (EAN) or MF (manufacturer)
   *
   * @return array LIN segment
   */
  public function setPosition($position, $content, $type = 'MF') {
    return [
      'LIN',
      $position,
      '',
      [
        $content,
        $type,
        // '',
        // '89'
      ],
    ];

    return $this;
  }

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
   * Builds a QTY segment with qualifier 12 (despatch quantity).
   *
   * @param int|string $quantity quantity
   * @param string     $unit     measure unit, e.g. PCE
   *
   * @return array
   */
  public static function addQTYSegment($quantity, $unit = 'PCE') {
    return [
      'QTY',
      [
        '12',
        $quantity,
        $unit,
      ],
    ];
  }



  /**
   * Sets the order number (RFF segment, VN) and order line number (RFF segment, LI).
   *
   * @param string     $orderNumber         order number of the buyer
   * @param string|int $orderNumberPosition line number in the order
   *
   * @return $this
   */
  public function setOrderNumber($orderNumber, $orderNumberPosition) {
    $this->orderNumber = $this->addRFFSegment('VN', $orderNumber);
    $this->orderNumberPosition = $this->addRFFSegment('LI', $orderNumberPosition);

    return $this;
  }

  /**
   * Sets the delivery note reference (RFF AAJ), its line number (RFF FI) and an
   * optional delivery date (DTM segment, qualifier 2).
   *
   * @param string                         $deliveryNoteNumber delivery note number
   * @param string|int                     $positon            line number on the delivery note
   * @param string|\DateTimeInterface|null $deliveryDate       delivery date, omitted when empty
   *
   * @return $this
   * @throws EdifactException if the delivery date is invalid
   */
  public function setDeliveryNoteNumber($deliveryNoteNumber, $positon, $deliveryDate = null) {
    $data[] = $this->addRFFSegment('AAJ', $deliveryNoteNumber);
    $data[] = $this->addRFFSegment('FI', $positon);

    if ($deliveryDate) {
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
}
