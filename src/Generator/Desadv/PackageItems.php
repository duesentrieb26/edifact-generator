<?php

namespace EDI\Generator\Desadv;


use EDI\Generator\Base;
use EDI\Generator\EdifactDate;
use EDI\Generator\EdifactException;

/**
 * Class Item
 * @package EDI\Generator\Desadv
 */
class PackageItem extends Base {

  protected $cps;

  protected $quantity;

  protected $content;

  protected $cpsMainCounter;

  protected $cpsCounter;

  protected $weight;

  protected $orderNumber;

  protected $orderNumberPosition;

  protected $deliveryNoteNumber;

  protected $deliveryNotePosition;

  protected $pac;

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
   * 
   * @return void 
   */
  public function __construct() {
  }



  /**
   * 
   * @param int $position 
   * @param string $content 
   * @param string $type 
   * @return $this 
   */
  public function setPackageContent($position, $content, $type, $quantity = 1) {
    $this->content = $this->setPosition($position, $content, $type);
    $this->quantity = $this->addQTYSegment($quantity);

    return $this;
  }


  /**
   * 
   * @param string $type [BB, BG, BH, BK, CF, CG, CH, CT, PA, PC, PG, PN, PU, SC, TU]
   * @param int $quantity 
   * @return $this 
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
   * @param integer $position 
   * @param string $content EAN nummer oder manufacturer articleNumber
   * @param string $type EN|MF
   *
   * @return self
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
   * @return array
   * @throws \EDI\Generator\EdifactException
   */
  public function compose() {
    return $this->composeByKeys($this->composeKeys);
  }



  /**
   * 
   * @param string $quantity 
   * @return (string|(string|int)[])[] 
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
   * Order Number
   * @param string $orderNumber 
   * @param string $orderNumberPosition 
   * @return $this 
   */
  public function setOrderNumber($orderNumber, $orderNumberPosition) {
    $this->orderNumber = $this->addRFFSegment('VN', $orderNumber);
    $this->orderNumberPosition = $this->addRFFSegment('LI', $orderNumberPosition);

    return $this;
  }

  /**
   * 
   * @param string $deliveryNoteNumber 
   * @param string $deliveryDate 
   * @return $this 
   * @throws EdifactException 
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
