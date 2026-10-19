<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 09:41
 */

namespace EDI\Generator;

/**
 * Thrown when a message cannot be built from the given data, e.g. an unknown code,
 * an invalid date or a message without content.
 *
 * @package EDI\Generator
 */
class EdifactException extends \Exception {
}
