<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 13:25
 */

namespace EDI\Generator;

/**
 * Base class for messages and line items. Collects the composed segments and
 * provides builders for common segments (RFF, DTM, BGM, MOA, PAT, PCD, PIA).
 *
 * @package EDI\Generator
 * @property array $composeKeys Ordered property names that composeByKeys() turns into segments
 */
class Base {

  /** @var array Segments collected so far, each segment is an array */
  protected $messageContent = [];

  /** @var array|null Finished segment list, set by compose() */
  protected $composed;

  /** @var string|null Sender identification, default party id of NAD segments */
  protected $sender;

  /** @var string|null Receiver identification */
  protected $receiver;

  /**
   * Adds a property name to the compose list.
   *
   * Without $position the key is appended to $composeKeys. With $position it is
   * inserted after that key into $array, which must be passed by reference.
   *
   * @param string      $keyName  Property name whose segment(s) should be composed
   * @param array|null  $array    Key list to insert into; defaults to a copy of $composeKeys
   * @param string|null $position Existing key after which $keyName is inserted
   *
   * @return void
   */
  public function addKeyToCompose($keyName, &$array = null, $position = null) {
    if (!$array) {
      $array = $this->composeKeys;
    }

    if ($position) {
      $index = array_search($position, $array);
      array_splice($array, $index + 1, 0, $keyName);

      return;
    }

    array_push($this->composeKeys, $keyName);
  }


  /**
   * Appends the segments stored in the given properties to the message content, in key order.
   *
   * A property holds one segment or a list of segments; null properties are skipped.
   *
   * @param array|null $keys Property names; defaults to $composeKeys
   *
   * @return array All segments composed so far
   * @throws EdifactException If a property is set but empty, or holds no array
   */
  public function composeByKeys($keys = null) {
    if (is_null($keys)) {
      $keys = $this->composeKeys;
    }
    foreach ($keys as $key) {
      if (property_exists($this, $key)) {
        if (!is_null($this->{$key})) {
          $value = $this->{$key};
          if ($value) {
            if (is_string($value[0])) {
              $this->messageContent[] = $value;
            } else {
              if (!is_array($value)) {
                throw new EdifactException("key " . $key . " returns no array structure");
              }

              foreach ($value as $item) {
                $this->messageContent[] = $item;
              }
            }
          } else {
            throw new EdifactException("key " . $key . " returns null");
          }
        }
      }
    }

    return $this->messageContent;
  }

  /**
   * Returns the finished segment list.
   *
   * @return array|null Null until compose() was called
   */
  public function getComposed() {
    return $this->composed;
  }

  /**
   * Returns the sender identification.
   *
   * @return string|null
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * Sets the sender identification, used as default party id in NAD segments.
   *
   * @param string $sender Sender identification, e.g. a GLN
   *
   * @return $this
   */
  public function setSender($sender) {
    $this->sender = $sender;

    return $this;
  }

  /**
   * Returns the receiver identification.
   *
   * @return string|null
   */
  public function getReceiver() {
    return $this->receiver;
  }

  /**
   * Sets the receiver identification.
   *
   * @param string $receiver Receiver identification, e.g. a GLN
   *
   * @return $this
   */
  public function setReceiver($receiver) {
    $this->receiver = $receiver;

    return $this;
  }


  /**
   * Builds a reference segment (RFF).
   *
   * @param string     $functionCode Reference qualifier (DE 1153), e.g. VA, FC, VN, LI
   * @param string|int $identifier   Reference number, cut to 35 characters
   *
   * @return array|null Null for an empty identifier, so composeByKeys() skips the segment
   */
  protected function addRFFSegment($functionCode, $identifier) {
    if (empty($identifier)) {
      return null;
    }

    return [
      'RFF',
      [
        $functionCode,
        self::maxChars($identifier, 35),
      ],
    ];
  }

  /**
   * Builds a date/time segment (DTM).
   *
   * @param string|\DateTimeInterface|null $dateString      Date; when empty only the qualifier is sent
   * @param string|int                     $type            Date/time qualifier (DE 2005), e.g. 3 invoice date, 35 delivery date
   * @param int                            $formatQualifier Format code (DE 2379): EdifactDate::DATE (102), DATETIME (203) or SHIPPING_WEEK (616)
   *
   * @return array
   * @throws EdifactException If the date cannot be parsed or does not exist
   * @link https://www.stylusstudio.com/edifact/D94A/2379.htm
   */
  public function addDTMSegment($dateString, $type, $formatQualifier = EdifactDate::DATE) {
    $data = [];
    array_push($data, $type);
    if (!empty($dateString)) {
      array_push($data, EdifactDate::get($dateString, $formatQualifier));
      array_push($data, $formatQualifier);
    }

    return [
      'DTM',
      $data,
    ];
  }

  /**
   * Builds the beginning-of-message segment (BGM); the document name code is sent with code list agency 89.
   *
   * @param string     $documentNumber Document number
   * @param string     $type           Document name code (DE 1001), e.g. 380 invoice, 381 credit note
   * @param int|string $x              Message function code (DE 1225): Invoic::TYPE_ORIGINAL (9), TYPE_COPY (31) or TYPE_REVERSAL (1)
   *
   * @return array
   */
  public static function addBGMSegment($documentNumber, $type, $x = Invoic::TYPE_ORIGINAL) {
    return [
      'BGM',
      [
        $type,
        '',
        '89',
      ],
      $documentNumber,
      $x,
    ];
  }

  /**
   * Trims a string and cuts it to a maximum number of characters (multibyte safe).
   *
   * @param string|null $string Text to cut
   * @param int         $length Maximum number of characters
   *
   * @return string Empty string for empty input
   */
  protected static function maxChars($string, $length = 35) {
    if (empty($string)) {
      return '';
    }

    return mb_substr(trim($string), 0, $length);
  }

  /**
   * Checks that a value is one of the allowed codes (loose comparison).
   *
   * @param mixed       $value        Value to check
   * @param array       $array        Allowed values
   * @param string|null $errorMessage Custom message; defaults to one naming the allowed values and the calling method
   *
   * @return void
   * @throws EdifactException If the value is not allowed
   */
  protected function isAllowed($value, $array, $errorMessage = null) {
    if (is_null($errorMessage)) {
      $errorMessage = 'value: ' . $value . ' is not in allowed values: ' .
        ' [' . implode(', ', $array) . '] in ' . get_class($this) . '->' .
        debug_backtrace()[1]['function'];
    }
    if (!in_array($value, $array)) {
      throw new EdifactException($errorMessage);
    }
  }


  /**
   * Builds a monetary amount segment (MOA).
   *
   * @param string|int       $qualifier Amount type (DE 5025), e.g. 8 allowance/charge, 9 payable, 79 line total, 125 taxable, 150 tax
   * @param string|int|float $value     Amount, formatted with EdiFactNumber::convert()
   *
   * @return array
   */
  public static function addMOASegment($qualifier, $value) {
    return [
      'MOA',
      [
        $qualifier,
        EdiFactNumber::convert($value),
      ],
    ];
  }


  const PAT_VALUTA = '3';
  const PAT_SKONTO = '22';
  const PAT_NET_PAYMENT_TARGET = 'ZZZ';
  const PAT_TIME_INVOICE_DATE = 5;

  /**
   * Builds a payment terms segment (PAT).
   *
   * @param string          $type  Payment terms type (DE 4279): PAT_SKONTO (22, cash discount), PAT_NET_PAYMENT_TARGET (ZZZ) or PAT_VALUTA (3)
   * @param int|string      $value Time reference (DE 2475), default PAT_TIME_INVOICE_DATE (5, invoice date)
   * @param int|string|null $days  Number of days after the reference date; omitted when empty
   *
   * @return array
   */
  public static function addPATSegment($type, $value = self::PAT_TIME_INVOICE_DATE, $days = null) {
    $valueArray = [
      $value,
    ];

    if ($days) {
      $valueArray[] = '';
      $valueArray[] = '';
      $valueArray[] = $days;
    }

    return [
      'PAT',
      [
        $type,
      ],
      '',
      $valueArray,
    ];
  }


  const PCD_CASH_DISCOUNT = '12';

  /**
   * Builds a percentage segment (PCD) for a cash discount (qualifier 12).
   *
   * @param string|int|float $value Percentage, formatted with EdiFactNumber::convert()
   *
   * @return array
   */
  public static function addPCDSegment($value) {
    return [
      'PCD',
      [
        self::PCD_CASH_DISCOUNT,
        EdiFactNumber::convert($value),
      ],
    ];
  }

  const PIA_ADDITIONAL_INFORMATION = 1;

  /**
   * Builds an additional product id segment (PIA) with the article's EAN.
   *
   * @param string $ean EAN (GTIN) of the article
   *
   * @return array
   */
  public static function addPIASegment($ean) {
    return [
      'PIA',
      [
        self::PIA_ADDITIONAL_INFORMATION,
      ],
      [
        $ean,
        'EN',
      ],
    ];
  }
}
