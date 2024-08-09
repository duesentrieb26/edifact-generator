<?php

namespace EDI\Generator\Desadv;


use EDI\Generator\Base;
use EDI\Generator\EdiFactNumber;

use function PHPSTORM_META\map;

/**
 * Class Item
 * @package EDI\Generator\Desadv
 */
class PackageItem extends Base {

  protected $cas;


  protected $composeKeys = [
    'cas',
    'packageQuantity',
    'packageNumber',
    'packageWeight',
  ];


  /**
   * 
   * @param mixed $quantity 
   * @return void 
   */
  public function setPackageQuantity($quantity) {
    $this->cas = $this->addCPSSegment($quantity);

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
}
