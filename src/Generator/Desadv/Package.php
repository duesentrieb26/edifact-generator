<?php

namespace EDI\Generator\Desadv;

use EDI\Generator\Base;
use EDI\Generator\EdiFactNumber;

class Package extends Base {


  protected $cas;

  protected $packageQuantity;

  protected $packageNumber;

  protected $packageWeight;

  protected $packageSignature;

  protected $packageHasNVE;

  protected $items = [];

  protected $cps;

  protected $composeKeys = [
    'cps',
    'packageWeight',
    'packageHasNVE',
    'packageNumber',
    'packageSignature',
    'packageQuantity',
    'items'
  ];


  protected $cpsCount = 1;


  public function __construct($initialCpsCount) {
    $this->cps = $this->addCPSSegment($this->cpsCount);
  }

  /**
   * 
   * @param mixed $quantity 
   * @return self 
   */
  public function setPackageQuantity($quantity, $unit) {
    $this->packageQuantity = $this->addPACSegment($quantity, $unit);

    return $this;
  }


  public function setPackageNumber($number) {
    $this->packageHasNVE = $this->addPCISegment();
    $this->packageNumber = $this->addGINSegment($number);

    return $this;
  }



  /**
   * @param $item Item
   */
  public function addItem(PackageItem $item) {
    $this->items[] = $item;

    return $this;
  }



  /**
   * 
   * @param mixed $weight 
   * @return $this 
   */
  public function setPackageWeight($weight) {
    $this->packageWeight = $this->addMEASegment($weight);

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
        89
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
   * Package number
   * @param string $number 
   * @return (string|array)[] 
   */
  public static function addCPSSegment($number) {
    return [
      'CPS',
      [
        '1',
        $number,
      ],
    ];
  }


  /**
   * Package quantity and type
   * @param float $quantity 
   * @param string $type BB, BG, BH, BK, CF, CG, CH, CT, PA, PC, PG, PN, PU, SC, TU
   * @return (string|array)[] 
   */
  public static function addPACSegment($quantity, $type = 'PK') {
    return [
      'PAC',
      [
        $quantity,
        $type,
      ],
    ];
  }

  /**
   * Weight 
   * @param float $weight 
   * @param string $unit  
   * @param string $type AAI, ABJ, BW, DI, DP, DW, FN, HT, LN, VW, WD
   * @return (string|string[])[] 
   */
  public static function addMEASegment($weight, $unit = 'KGM', $type = 'BW') {
    return [
      'MEA',
      [
        'AAE', // Dimension
        $type,
        $unit,
        EdiFactNumber::convert($weight),
      ],
    ];
  }

  /**
   * Package signature
   * @param string $signature 33E, 12
   * @return (string|string[])[] 
   */
  public static function addPCISegment($code = '33E') {
    return [
      'PCI',
      [
        '1',
        $code
      ],
    ];
  }

  /**
   * Goods item number
   * @param string $trackingCode 
   * @param string $type 
   * @return (string|array)[] 
   */
  public static function addGINSegment($trackingCode, $type = 'BJ') {
    return [
      'GIN',
      [
        $type,
        $trackingCode,
      ],
    ];
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
