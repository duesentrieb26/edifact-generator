<?php

namespace EDI\Generator\Desadv;


use EDI\Generator\Base;


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


  protected $composeKeys = [
    'cps',
    'quantity',
    'content',
  ];



  /**
   * 
   * @param int &$cpsCounter 
   * @param int &$cpsMainCounter  
   * @return void 
   */
  public function __construct(&$cpsMainCounter, &$cpsCounter, $totalPackages) {
    // if (!$cpsMainCounter) {
    //   throw new \InvalidArgumentException('CPS main counter is required');
    // }

    // if (!$cpsCounter) {
    //   throw new \InvalidArgumentException('CPS counter is required');
    // }

    // $this->cps = Package::addCPSSegment($cpsCounter, $cpsMainCounter);
    // $cpsCounter++;
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
}
