<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 08:44
 */

namespace EDI\Generator\Desadv;

use EDI\Generator\Base;

/**
 * DESADV line item without a package (LIN group with QTY, IMD, RFF and DTM segments).
 * All setters come from the Item trait.
 *
 * @package EDI\Generator\Desadv
 */
class Item extends Base {
    use \EDI\Generator\Traits\Item;
}
