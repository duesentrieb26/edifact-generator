<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 10:19
 */

namespace EDI\Generator;

/**
 * Formats dates for DTM segments (format codes, DE 2379) and holds common
 * date/time qualifiers (DE 2005).
 *
 * @package EDI\Generator
 */
class EdifactDate {
  const DATE = 102;
  const DATE_FORMAT = 'Ymd';

  const DATETIME = 203;
  const DATETIME_FORMAT = 'YmdHi';

  const SHIPPING_WEEK = 616;
  // ISO year (o), not calendar year (Y): 2018-12-31 is in week 01 of 2019
  const SHIPPING_WEEK_FORMAT = 'oW';

  const SHIPPING_UNDEFINED = 999;
  const SHIPPING_UNDEFINED_FORMAT = "";

  const TYPE_ORDER_DATE = 4;
  const TYPE_DELIVERY_DATE_REQUESTED = 2;
  const TYPE_DELIVERY_DATE_ESTIMATED = 17;
  const TYPE_DELIVERY_DATE_ACTUAL = 35;
  const TYPE_DEPARTURE_DATE_ESTIMATED = 133;


  /**
   * Formats a date in the given EDIFACT format.
   *
   * @param string|\DateTimeInterface|null $string Date; strings as 'Y-m-d', or 'Y-m-d H:i[:s]' for DATETIME
   * @param int                            $format Format code: DATE (102, CCYYMMDD), DATETIME (203, CCYYMMDDHHMM),
   *                                               SHIPPING_WEEK (616, CCYYWW) or SHIPPING_UNDEFINED (999, empty);
   *                                               other codes give CCYYMMDD
   *
   * @return string Formatted date, or '' for empty input
   * @throws EdifactException If the date cannot be parsed or does not exist
   */
  public static function get($string, $format = self::DATE) {
    if (empty($string)) {
      return "";
    }
    switch ($format) {
      case self::DATE:
        $dateFormat = self::DATE_FORMAT;
        break;

      case self::DATETIME:
        $dateFormat = self::DATETIME_FORMAT;
        break;

      case self::SHIPPING_WEEK:
        $dateFormat = self::SHIPPING_WEEK_FORMAT;
        break;

      case self::SHIPPING_UNDEFINED:
        $dateFormat = self::SHIPPING_UNDEFINED_FORMAT;
        break;

      default:
        $dateFormat = self::DATE_FORMAT;
    }
    $dateTime = self::parseFormat($string, $format);
    if (!$dateTime) {
      throw new EdifactException('invalid date provided: ' . $string);
    }

    return $dateTime->format($dateFormat);
  }

  /**
   * Parses a date string; a DateTimeInterface is returned unchanged.
   *
   * Impossible dates such as 2018-02-30 return false instead of rolling over.
   *
   * @param string|\DateTimeInterface $string Date to parse
   * @param int                       $format DATETIME expects 'Y-m-d H:i:s' or 'Y-m-d H:i'; DATE uses the first
   *                                          10 characters as 'Y-m-d'; all other codes expect 'Y-m-d'
   *
   * @return \DateTimeInterface|false
   */
  public static function parseFormat($string, $format = self::DATE) {
    if ($string instanceof \DateTimeInterface) {
      return $string;
    }

    $parseFormat = 'Y-m-d';
    switch ($format) {
      case self::DATE:
        $string = substr($string, 0, 10);
        $parseFormat = 'Y-m-d';
        break;
      case

      self::DATETIME:
        $parseFormat = 'Y-m-d H:i:s';
        if (strlen($string) == 16) {
          $parseFormat = 'Y-m-d H:i';
        }
        break;
    }

    $dateTime = \DateTime::createFromFormat($parseFormat, $string);
    // createFromFormat rolls impossible dates over (2018-02-30 -> 2018-03-02) and only warns
    $errors = \DateTime::getLastErrors();
    if ($errors && $errors['warning_count'] > 0) {
      return false;
    }

    return $dateTime;
  }
}
