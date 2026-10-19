<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 08.02.2018
 * Time: 15:37
 */

namespace EDI\Generator;

/**
 * Formats numbers for EDIFACT data elements such as amounts, prices and percentages.
 *
 * @package EDI\Generator
 */
class EdiFactNumber {
    const DECIMAL_COMMA = ',';
    const DECIMAL_POINT = '.';

    /**
     * Formats a number with a fixed number of decimals and no thousands separator.
     *
     * Strings with a decimal comma, also in German format ("1.234,56"), are accepted.
     *
     * @param string|int|float|null $value    Number; null gives '0,00'
     * @param int                   $decimals Number of decimals
     * @param string                $format   Decimal mark: DECIMAL_COMMA (default) or DECIMAL_POINT
     *
     * @return string
     */
    public static function convert($value, $decimals = 2, $format = self::DECIMAL_COMMA) {
        if ($value === null) {
            return '0,00';
        }
        if (!is_numeric($value)) {
            if (str_contains($value, ',')) {
                // German format: dots group thousands, the comma is the decimal mark ("1.234,56")
                $value = str_replace(['.', ','], ['', '.'], $value);
            }
            $value = floatval($value);
        }

        return number_format($value, $decimals, $format, '');
    }
}
