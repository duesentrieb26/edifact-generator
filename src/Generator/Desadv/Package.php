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

  protected $globalCps;

  protected $totalPackages;

  protected $totalWeight;

  protected $composeKeys = [
    'globalCps',
    'totalPackages',
    'totalWeight',
    'cps',
    'packageQuantity',
    'packageWeight',
    'packageHasNVE',
    'packageNumber',
    'packageSignature',
  ];



  /**
   * 
   * @return void 
   */
  public function __construct() {
  }

  /**
   * Set Package quantity and unit
   * 
   * @param int $quantity 
   * @param string $unit [BB, BG, BH, BK, CF, CG, CH, CT, PA, PC, PG, PN, PU, SC, TU]
   * @return self 
   */
  public function setPackageQuantity(int $quantity, string $unit) {
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
      'TU'
    ];
    if (!in_array($unit, $allowedUnits)) {
      throw new \InvalidArgumentException('Invalid unit ' . $unit . ' for package quantity. Only these are allowed ' . implode(', ', $allowedUnits));
    }
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
    $this->packageWeight = $this->addMEASegment($weight, 'AAE', 'BW', 'KGM');

    return $this;
  }

  /**
   * 
   * @return (string|string[])[] 
   */
  public function getPackageWeight() {
    return $this->packageWeight;
  }



  /**
   * 
   * @return PackageItem[]
   */
  public function getItems() {
    return $this->items;
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
   * @param integer $number 
   * @return (string|array)[] 
   */
  public static function addCPSSegment(int $counter, int $mainCounter = 1) {
    $sublevel = null;
    if ($mainCounter > 1) {
      $sublevel = [$counter];
    }

    $result = [
      'CPS',
      $mainCounter,
    ];

    if ($sublevel) {
      $result[] = $sublevel;
    }
    return $result;
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
      ],
      '',
      $type,
    ];
  }

  /**
   * Weight and dimensions
   * @param float $weight weight with max 3 decimal places and a comma as decimal separator
   * @param string $dimension [AAE]
   * @param string $unit [AAI, ABJ, BW, DI, DP, DW, FN, HT, LN, VW, WD]  
   * @param string $qualifier [CMK, CMQ, CMT, DZN, GRM, HLT, KGM, KTM, LTR, MMT, MTK, MTQ, NRL, MTR, PCE, PR, SET, TNE] 
   * @return (string|string[])[] 
   */
  public static function addMEASegment($weight, $dimension = 'AAE', $unit = 'BW', $qualifier = 'KGM') {
    $allowedDimensions = [
      'AAE',
    ];
    if (!in_array($dimension, $allowedDimensions)) {
      throw new \InvalidArgumentException('Invalid dimension ' . $dimension .
        ' for package weight. Only these are allowed ' . implode(', ', $allowedDimensions));
    }
    $allowedUnits = [
      'AAI',
      'ABJ',
      'BW',
      'DI',
      'DP',
      'DW',
      'FN',
      'HT',
      'LN',
      'VW',
      'WD',
    ];
    if (!in_array($unit, $allowedUnits)) {
      throw new \InvalidArgumentException('Invalid unit ' . $unit .
        ' for package weight. Only these are allowed ' . implode(', ', $allowedUnits));
    }
    $allowedQualifiers = [
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
      'NRL',
      'MTR',
      'PCE',
      'PR',
      'SET',
      'TNE',
    ];
    if (!in_array($qualifier, $allowedQualifiers)) {
      throw new \InvalidArgumentException('Invalid qualifier ' . $qualifier . ' for package weight. Only these are allowed ' . implode(', ', $allowedQualifiers));
    }

    return [
      'MEA',
      $dimension,
      $unit,
      [
        $qualifier,
        EdiFactNumber::convert($weight)
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
      $type,
      [
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
      '12',
      [
        $quantity,
      ],
    ];
  }
}
