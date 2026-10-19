<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 08:44
 */

namespace EDI\Generator\Ordrsp;

use EDI\Generator\Base;

/**
 * One line item of an ORDRSP message (LIN with quantity, texts, dates and references
 * from the Item trait, plus gross and net prices from the ItemPrice trait).
 *
 * @package EDI\Generator\Ordrsp
 */
class Item extends Base {
    use \EDI\Generator\Traits\Item;

    use \EDI\Generator\Traits\ItemPrice;
}
