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

  protected $deliveryNoteNumber;

  protected $composeKeys = [
    'cps',
    'quantity',
    'content',
    'deliveryNoteNumber'
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
        '',
        '89'
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
  public static function addQTYSegment($quantity) {
    return [
      'QTY',
      [
        '12',
        $quantity,
      ],
    ];
  }


  /**
   * 
   * @param mixed $deliveryNoteNumber 
   * @param mixed $deliveryDate 
   * @return $this 
   * @throws EdifactException 
   */
  public function setDeliveryNoteNumber($deliveryNoteNumber, $positon = null, $deliveryDate = null) {
    $data[] = $this->addRFFSegment('AAJ', $deliveryNoteNumber);


    if ($positon) {
      $data[] = $this->addRFFSegment('LI', $positon);
    }

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
