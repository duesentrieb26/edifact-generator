<?php

namespace EDI\Generator\Desadv;

use EDI\Generator\Base;
use EDI\Generator\EdiFactNumber;

/**
 * DESADV package: one CPS group with package quantity (PAC), weight (MEA),
 * SSCC/NVE marking (PCI, GIN) and the package items packed inside.
 * Also provides the static segment builders that Desadv uses for the consignment level.
 *
 * @package EDI\Generator\Desadv
 */
class Package extends Base {

  /** @var array|null unused, never set */
  protected $cas;

  /** @var array|null PAC segment: number and type of packages */
  protected $packageQuantity;

  /** @var array|null GIN segment with the SSCC/NVE package number */
  protected $packageNumber;

  /** @var array|null MEA segment with the gross weight in KGM */
  protected $packageWeight;

  /** @var array|null unused, never set */
  protected $packageSignature;

  /** @var array|null PCI segment marking the package as SSCC labelled (33E) */
  protected $packageHasNVE;

  /** @var PackageItem[] items packed in this package */
  protected $items = [];

  /** @var array|null unused, never set */
  protected $cps;

  /** @var array|null unused, never set */
  protected $globalCps;

  /** @var array|null unused, never set */
  protected $totalPackages;

  /** @var array|null unused, never set */
  protected $totalWeight;

  /** @var string[] property names composed in this order */
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
   * Creates an empty package.
   */
  public function __construct() {
  }

  /**
   * Sets the number and type of packages (PAC segment).
   *
   * @param int    $quantity number of packages
   * @param string $unit     package type: BB, BG, BH, BK, CF, CG, CH, CT, PA, PC, PG, PN, PU, SC, TU
   *
   * @return $this
   * @throws \InvalidArgumentException if the package type is not allowed
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


  /**
   * Sets the SSCC/NVE package number (GIN segment, type BJ) and marks the package
   * as SSCC labelled (PCI segment, 33E).
   *
   * @param string $number SSCC/NVE number of the package
   *
   * @return $this
   */
  public function setPackageNumber($number) {
    $this->packageHasNVE = $this->addPCISegment();
    $this->packageNumber = $this->addGINSegment($number);

    return $this;
  }



  /**
   * Adds an item that is packed in this package.
   *
   * @param PackageItem $item
   *
   * @return $this
   */
  public function addItem(PackageItem $item) {
    $this->items[] = $item;

    return $this;
  }



  /**
   * Sets the gross weight of the package in kilograms (MEA segment, AAE+BW+KGM).
   *
   * @param string|int|float $weight weight in KGM
   *
   * @return $this
   */
  public function setPackageWeight($weight) {
    $this->packageWeight = $this->addMEASegment($weight, 'AAE', 'BW', 'KGM');

    return $this;
  }

  /**
   * Returns the weight segment (MEA), or null if no weight was set.
   *
   * @return array|null
   */
  public function getPackageWeight() {
    return $this->packageWeight;
  }



  /**
   * Returns the items packed in this package.
   *
   * @return PackageItem[]
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * Returns the package segments in compose order (without the CPS segment and the items).
   *
   * @return array list of segments
   * @throws \EDI\Generator\EdifactException if a set property holds no segment
   */
  public function compose() {
    $this->messageContent = [];

    return $this->composeByKeys($this->composeKeys);
  }

  /**
   * Builds a CPS segment (consignment packing sequence).
   *
   * @param int $counter     parent hierarchical id, only added when $mainCounter > 1
   * @param int $mainCounter hierarchical id of this package level
   *
   * @return array
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
   * Builds a PAC segment with number and type of packages.
   *
   * @param int|float   $quantity number of packages
   * @param string|null $type     package type code (e.g. CT, PN, PK); empty omits the type
   *
   * @return array
   */
  public static function addPACSegment($quantity, $type = 'PK') {
    $result =  [
      'PAC',
      [
        $quantity,
      ],
    ];

    if ($type) {
      $result[] = '';
      $result[] = $type;
    }

    return $result;
  }

  /**
   * Builds a MEA segment for a weight or dimension.
   *
   * @param string|int|float $weight    value, formatted with 2 decimals and a decimal comma
   * @param string           $dimension measurement purpose: AAE
   * @param string           $unit      measured attribute: AAI, ABJ, BW, DI, DP, DW, FN, HT, LN, VW, WD
   * @param string           $qualifier measure unit: CMK, CMQ, CMT, DZN, GRM, HLT, KGM, KTM, LTR, MMT, MTK, MTQ, NRL, MTR, PCE, PR, SET, TNE
   *
   * @return array
   * @throws \InvalidArgumentException if dimension, unit or qualifier is not allowed
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
   * Builds a PCI segment (package marking instructions).
   *
   * @param string $code marking code, e.g. 33E (marked with SSCC barcode) or 12
   *
   * @return array
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
   * Builds a GIN segment (goods identity number).
   *
   * @param string $trackingCode identity number, e.g. the SSCC/NVE
   * @param string $type         identity number qualifier, BJ = serial shipping container code
   *
   * @return array
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
   * Builds a QTY segment with qualifier 12 (despatch quantity).
   *
   * @param string|int $quantity
   *
   * @return array
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
