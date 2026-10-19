<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 16:15
 */

namespace EDI\Generator;

use EDI\Generator\Traits\ContactPerson;
use EDI\Generator\Traits\DeliveryTerms;
use EDI\Generator\Traits\NameAndAddress;

/**
 * INVOIC message (invoice or credit note) in the ITEK35 profile of UN/EDIFACT D.96B.
 * Header segments are composed in the order of $composeKeys, then the line items,
 * then the UNS separator and the summary segments from $composeKeysAfterPositions.
 *
 * @link http://www.unece.org/trade/untdid/d96b/trmd/invoic_s.htm
 * @link http://www.stylusstudio.com/edifact/D96A/INVOIC.htm
 *
 * @package EDI\Generator
 */
class Invoic extends Message {
  use
    ContactPerson,
    DeliveryTerms,
    NameAndAddress;

  /**
   * Document type 380: commercial invoice.
   */
  const TYPE_INVOICE = '380';
  /**
   * Document type 381: credit note.
   */
  const TYPE_CREDIT_NOTE = '381';
  /**
   * Document type 31e (ITEK): service credit note.
   */
  const TYPE_SERVICE_CREDIT = '31e';
  /**
   * Document type 32e (ITEK): service invoice.
   */
  const TYPE_SERVICE_INVOICE = '32e';
  /**
   * Document type 33i (ITEK): bonus credit note.
   */
  const TYPE_BONUS = '33i';

  /**
   * Message function code 1: cancellation (Storno).
   */
  const TYPE_REVERSAL = 1;
  /**
   * Message function code 9: original.
   */
  const TYPE_ORIGINAL = 9;
  /**
   * Message function code 31: copy (Kopie).
   */
  const TYPE_COPY = 31;

  /** @var array|null BGM segment with the invoice number */
  protected $invoiceNumber;
  /** @var array|null DTM segment, invoice date (qualifier 3) */
  protected $invoiceDate;
  /** @var array|null DTM segment, delivery date (qualifier 35) */
  protected $deliveryDate;
  /** @var Invoic\Item[] line items, composed after the header */
  protected $items;
  /** @var array|null FTX segment, reduction of fees text */
  protected $reductionOfFeesText;
  /** @var array|null FTX segment, text for amounts excluding VAT */
  protected $excludingVatText;
  /** @var array|null FTX segment, free invoice description */
  protected $invoiceDescription;
  /** @var array|null RFF segment, own VAT number (qualifier VA) */
  protected $vatNumber;
  /** @var array|null RFF segment, customer VAT number (qualifier FC) */
  protected $customerVatNumber;
  /** @var array|null CUX segment, invoice currency */
  protected $currency;

  /** @var array|null UNS segment between line items and summary */
  protected $positionSeparator;

  /** @var array|null MOA segment, total of all line items (qualifier 79) */
  protected $totalPositionsAmount;
  /** @var array|null MOA segment, basis amount (qualifier 56) */
  protected $basisAmount;
  /** @var array|null MOA segment, taxable amount (qualifier 125) */
  protected $taxableAmount;
  /** @var array|null MOA segment, amount payable (qualifier 9) */
  protected $payableAmount;
  /** @var array|null TAX segment with the VAT rate */
  protected $tax;
  /** @var array|null MOA segment, VAT amount (qualifier 150) */
  protected $taxAmount;

  /** @var int counter for the net payment terms added with addNetAmount() */
  protected $index = 0;

  /** @var array[]|null ALC and MOA segments of invoice level charges */
  protected $charges;

  /** @var array[]|null PAT, DTM and PCD segments of the cash discount terms */
  protected $cashDiscount;

  /** @var array|null DTM segment of the first net payment term, later terms use netAmountDTM1, ... */
  protected $netAmountDTM0;

  /** @var array|null PAT segment of the first net payment term, later terms use netAmountPAT1, ... */
  protected $netAmountPAT0;

  /** @var array|null RFF segment, fiscal number of the invoice address (qualifier FC) */
  protected $invoiceAddressFiscalNumber;

  /** @var string[] property names composed before the line items, in output order */
  protected $composeKeys
  = [
    'invoiceNumber',
    'invoiceDate',
    'deliveryDate',
    'reductionOfFeesText',
    'excludingVatText',
    'invoiceDescription',
    'manufacturerAddress',
    'manufacturerAddressVatId',
    'wholesalerAddress',
    'wholesalerAddressVatId',
    'representativeAddress',
    'representativeAddressVatId',
    'deliveryAddress',
    'deliveryAddressVatId',
    'deliveryTerms',
    'invoiceAddress',
    'invoiceAddressVatId',
    'invoiceAddressFiscalNumber',
    'contactPerson',
    'mailAddress',
    'phoneNumber',
    'faxNumber',
    'vatNumber',
    'customerVatNumber',
    'currency',
    'cashDiscount',
  ];

  /** @var string[] property names composed after the line items (summary section), in output order */
  protected $composeKeysAfterPositions
  = [
    'positionSeparator',
    'totalPositionsAmount',
    'taxableAmount',
    'basisAmount',
    'payableAmount',
    'tax',
    'taxAmount',
    'charges',
  ];


  /**
   * Creates an INVOIC message, by default D.96B UN with association ITEK35.
   *
   * @param string|null $messageId         message reference for UNH/UNT, generated when null
   * @param string      $identifier        message type
   * @param string      $version           message version number
   * @param string      $release           message release number
   * @param string      $controllingAgency controlling agency
   * @param string      $association       association assigned code
   */
  public function __construct(
    $messageId = null,
    $identifier = 'INVOIC',
    $version = 'D',
    $release = '96B',
    $controllingAgency = 'UN',
    $association = 'ITEK35'
  ) {
    parent::__construct(
      $identifier,
      $version,
      $release,
      $controllingAgency,
      $messageId,
      $association
    );
    $this->items = [];
  }


  /**
   * Adds a line item; its segments are composed after the header.
   *
   * @param Invoic\Item $item
   *
   * @return $this
   */
  public function addItem($item) {
    $this->items[] = $item;

    return $this;
  }


  /**
   * Builds the message: header segments, line items, UNS separator and summary,
   * wrapped in UNH/UNT. Calling it again rebuilds the message from scratch.
   *
   * @param mixed $msgStatus not used
   *
   * @return $this
   * @throws EdifactException if a header value is invalid or the message is empty
   */
  public function compose($msgStatus = null) {
    $this->messageContent = [];
    $this->composeByKeys();

    foreach ($this->items as $item) {
      $composed = $item->compose();
      foreach ($composed as $entry) {
        $this->messageContent[] = $entry;
      }
    }

    $this->setPositionSeparator();
    $this->composeByKeys($this->composeKeysAfterPositions);

    parent::compose();
    return $this;
  }

  /**
   * Returns the invoice number (BGM segment).
   *
   * @return array|null
   */
  public function getInvoiceNumber() {
    return $this->invoiceNumber;
  }

  /**
   * Sets the invoice number and document type (BGM segment).
   *
   * @param string $invoiceNumber invoice or credit note number
   * @param string $documentType  TYPE_INVOICE, TYPE_CREDIT_NOTE, TYPE_SERVICE_CREDIT, TYPE_SERVICE_INVOICE or TYPE_BONUS
   * @param int    $type          message function code (TYPE_ORIGINAL, TYPE_REVERSAL, TYPE_COPY);
   *                              currently not passed on, BGM always sends 9 (original)
   *
   * @return $this
   * @throws EdifactException if the document type is not allowed
   */
  public function setInvoiceNumber($invoiceNumber, $documentType = self::TYPE_INVOICE, $type = self::TYPE_ORIGINAL) {
    $this->isAllowed(
      $documentType,
      [
        self::TYPE_INVOICE,
        self::TYPE_CREDIT_NOTE,
        self::TYPE_SERVICE_CREDIT,
        self::TYPE_SERVICE_INVOICE,
        self::TYPE_BONUS,
      ]
    );
    $this->invoiceNumber = self::addBGMSegment(
      $invoiceNumber,
      $documentType
    );

    return $this;
  }

  /**
   * Returns the invoice date (DTM segment, qualifier 3).
   *
   * @return array|null
   */
  public function getInvoiceDate() {
    return $this->invoiceDate;
  }

  /**
   * Sets the invoice date (DTM segment, qualifier 3).
   *
   * @param string|\DateTimeInterface $invoiceDate date as "Y-m-d" string or date object
   *
   * @return $this
   * @throws EdifactException if the date is invalid
   */
  public function setInvoiceDate($invoiceDate) {
    $this->invoiceDate = $this->addDTMSegment($invoiceDate, '3');
    return $this;
  }

  /**
   * Returns the delivery date (DTM segment, qualifier 35).
   *
   * @return array|null
   */
  public function getDeliveryDate() {
    return $this->deliveryDate;
  }

  /**
   * Sets the actual delivery date (DTM segment, qualifier 35).
   *
   * @param string|\DateTimeInterface $deliveryDate date as "Y-m-d" string or date object
   *
   * @return $this
   * @throws EdifactException if the date is invalid
   */
  public function setDeliveryDate($deliveryDate) {
    $this->deliveryDate = $this->addDTMSegment($deliveryDate, '35');
    return $this;
  }

  /**
   * Returns the reduction of fees text (FTX segment).
   *
   * @return array|null
   */
  public function getReductionOfFeesText() {
    return $this->reductionOfFeesText;
  }

  /**
   * Sets the reduction of fees text (FTX+OSI with reference HAE).
   *
   * @param string $reductionOfFeesText free text, split into lines of 70 characters
   *
   * @return $this
   */
  public function setReductionOfFeesText($reductionOfFeesText) {
    $this->reductionOfFeesText = self::addFTXSegment($reductionOfFeesText, 'OSI', 'HAE');
    return $this;
  }

  /**
   * Returns the text for amounts excluding VAT (FTX segment).
   *
   * @return array|null
   */
  public function getExcludingVatText() {
    return $this->excludingVatText;
  }

  /**
   * Sets the text for amounts excluding VAT (FTX+OSI with reference ROU).
   *
   * @param string $excludingVatText free text, split into lines of 70 characters
   *
   * @return $this
   */
  public function setExcludingVatText($excludingVatText) {
    $this->excludingVatText = self::addFTXSegment($excludingVatText, 'OSI', 'ROU');
    return $this;
  }

  /**
   * Returns the invoice description (FTX segment).
   *
   * @return array|null
   */
  public function getInvoiceDescription() {
    return $this->invoiceDescription;
  }

  /**
   * Sets a free invoice description (FTX+OSI without text reference).
   *
   * @param string $invoiceDescription free text, split into lines of 70 characters
   *
   * @return $this
   */
  public function setInvoiceDescription($invoiceDescription) {
    $this->invoiceDescription = self::addFTXSegment($invoiceDescription, 'OSI');
    return $this;
  }

  /**
   * Returns the own VAT number (RFF segment, qualifier VA).
   *
   * @return array|null
   */
  public function getVatNumber() {
    return $this->vatNumber;
  }

  /**
   * Sets the own VAT number (RFF segment, qualifier VA). Spaces are removed;
   * an empty value sends no segment.
   *
   * @param string $vatNumber VAT registration number
   *
   * @return $this
   */
  public function setVatNumber($vatNumber) {
    $this->vatNumber = self::addRFFSegment('VA', str_replace(' ', '', $vatNumber));
    return $this;
  }

  /**
   * Returns the customer VAT number (RFF segment, qualifier FC).
   *
   * @return array|null
   */
  public function getCustomerVatNumber() {
    return $this->customerVatNumber;
  }

  /**
   * Sets the customer VAT number (RFF segment, qualifier FC). Spaces are removed;
   * an empty value sends no segment.
   *
   * @param string $vatNumber VAT registration number of the customer
   *
   * @return $this
   */
  public function setCustomerVatNumber($vatNumber) {
    $this->customerVatNumber = self::addRFFSegment('FC', str_replace(' ', '', $vatNumber));
    return $this;
  }

  /**
   * Returns the invoice currency (CUX segment).
   *
   * @return array|null
   */
  public function getCurrency() {
    return $this->currency;
  }

  /**
   * Sets the invoice currency (CUX segment, qualifier 2 = reference currency).
   *
   * @param string $currency ISO 4217 currency code
   *
   * @return $this
   */
  public function setCurrency($currency = 'EUR') {
    $this->currency = [
      'CUX',
      [
        '2',
        $currency,
      ],
    ];
    return $this;
  }


  /**
   * Sets the UNS+S separator between line items and summary. compose() calls this itself.
   *
   * @return $this
   */
  public function setPositionSeparator() {
    $this->positionSeparator = [
      'UNS',
      'S',
    ];
    return $this;
  }

  /**
   * Returns the total of all line items (MOA segment, qualifier 79).
   *
   * @return array|null
   */
  public function getTotalPositionsAmount() {
    return $this->totalPositionsAmount;
  }

  /**
   * Sets the total of all line items (MOA segment, qualifier 79).
   *
   * @param string|int|float $totalPositionsAmount amount, "1.234,56" strings are accepted
   *
   * @return $this
   */
  public function setTotalPositionsAmount($totalPositionsAmount) {
    $this->totalPositionsAmount = self::addMOASegment('79', $totalPositionsAmount);
    return $this;
  }

  /**
   * Returns the basis amount (MOA segment, qualifier 56).
   *
   * @return array|null
   */
  public function getBasisAmount() {
    return $this->basisAmount;
  }

  /**
   * Sets the basis amount (MOA segment, qualifier 56).
   *
   * @param string|int|float $basisAmount amount, "1.234,56" strings are accepted
   *
   * @return $this
   */
  public function setBasisAmount($basisAmount) {
    $this->basisAmount = self::addMOASegment('56', $basisAmount);
    return $this;
  }

  /**
   * Returns the taxable amount (MOA segment, qualifier 125).
   *
   * @return array|null
   */
  public function getTaxableAmount() {
    return $this->taxableAmount;
  }

  /**
   * Sets the taxable amount (MOA segment, qualifier 125).
   *
   * @param string|int|float $taxableAmount amount, "1.234,56" strings are accepted
   *
   * @return $this
   */
  public function setTaxableAmount($taxableAmount) {
    $this->taxableAmount = self::addMOASegment('125', $taxableAmount);
    return $this;
  }

  /**
   * Returns the amount payable (MOA segment, qualifier 9).
   *
   * @return array|null
   */
  public function getPayableAmount() {
    return $this->payableAmount;
  }

  /**
   * Sets the amount payable (MOA segment, qualifier 9).
   *
   * @param string|int|float $payableAmount amount, "1.234,56" strings are accepted
   *
   * @return $this
   */
  public function setPayableAmount($payableAmount) {
    $this->payableAmount = self::addMOASegment('9', $payableAmount);

    return $this;
  }

  /**
   * Sets the VAT rate (TAX+7+VAT segment) and the VAT amount (MOA segment, qualifier 150).
   *
   * @param string|int|float $value  VAT rate in percent, e.g. 19
   * @param string|int|float $amount VAT amount
   *
   * @return $this
   */
  public function setTax($value, $amount) {
    $this->tax = [
      'TAX',
      '7',
      'VAT',
      '',
      '',
      [
        '',
        '',
        '',
        EdiFactNumber::convert($value, 2),
      ],
    ];
    $this->taxAmount = self::addMOASegment('150', $amount);
    return $this;
  }


  /**
   * Adds cash discount terms (Skonto): PAT+22, a DTM segment (qualifier 343) with the
   * due date and PCD+12 with the percentage. Can be called several times.
   *
   * @param string|\DateTimeInterface $date  date until the discount applies
   * @param string|int|float          $value discount in percent
   *
   * @return $this
   * @throws EdifactException if the date is invalid
   */
  public function addCashDiscount($date, $value) {
    if (!is_array($this->cashDiscount)) {
      $this->cashDiscount = [];
    }

    array_push(
      $this->cashDiscount,
      self::addPATSegment(Base::PAT_SKONTO)
    );

    array_push(
      $this->cashDiscount,
      self::addDTMSegment($date, 343)
    );


    array_push(
      $this->cashDiscount,
      self::addPCDSegment($value)
    );

    return $this;
  }

  /**
   * Adds a net payment term (Nettosumme): PAT+ZZZ with the number of days and the
   * due date (DTM segment, qualifier 13). Each call adds one more term.
   *
   * @param int                       $days number of days after the invoice date
   * @param string|\DateTimeInterface $date net due date
   *
   * @return $this
   * @throws EdifactException if the date is invalid
   */
  public function addNetAmount($days, $date) {
    $index = 'netAmountPAT' . $this->index;
    $this->{$index} = self::addPATSegment(Base::PAT_NET_PAYMENT_TARGET, Base::PAT_TIME_INVOICE_DATE, $days);
    $this->addKeyToCompose($index);

    $index = 'netAmountDTM' . $this->index;
    $this->{$index} = self::addDTMSegment($date, 13);
    $this->addKeyToCompose($index);

    $this->index++;

    return $this;
  }

  /** Charge type ABW: fees. */
  const CHARGES_TYPE_FEES = 'ABW';
  /** Charge type AU: notarization. */
  const CHARGES_TYPE_NOTARIZATION = 'AU';
  /** Charge type DL: delivery (freight). */
  const CHARGES_TYPE_CARGO = 'DL';
  /** Charge type IN: insurance. */
  const CHARGES_TYPE_INSURANCE = 'IN';
  /** Charge type PC: packing. */
  const CHARGES_TYPE_PACKING = 'PC';
  /** Charge type ZZZ: mutually defined. */
  const CHARGES_TYPE_CUSTOM = 'ZZZ';

  /**
   * Adds an invoice level charge or allowance (ALC segment) with its amount (MOA segment, qualifier 8).
   *
   * @param string|int|float $value amount; positive sends a charge (ALC+C), zero or negative an allowance (ALC+A)
   * @param string           $type  one of the CHARGES_TYPE_* codes
   *
   * @return $this
   */
  public function addCharges($value, $type = self::CHARGES_TYPE_CARGO) {
    if (!is_array($this->charges)) {
      $this->charges = [];
    }
    $this->charges[] = [
      'ALC',
      floatval($value) > 0 ? 'C' : 'A',
      '',
      '',
      '',
      $type,
    ];

    $this->charges[] = self::addMOASegment('8', EdiFactNumber::convert(abs($value)));

    return $this;
  }



  /**
   * Sets the VAT number of the invoice address (RFF segment, qualifier VA). Spaces are removed.
   *
   * @param string $vatNumber VAT registration number
   *
   * @return $this
   */
  public function setInvoiceAddressVatId($vatNumber) {
    $this->invoiceAddressVatId = self::addRFFSegment('VA', str_replace(' ', '', $vatNumber));

    return $this;
  }

  /**
   * Sets the fiscal number of the invoice address (RFF segment, qualifier FC). Spaces are removed.
   *
   * @param string $fiscalNumber tax number
   *
   * @return $this
   */
  public function setInvoiceAddressFiscalNumber($fiscalNumber) {
    $this->invoiceAddressFiscalNumber = self::addRFFSegment('FC', str_replace(' ', '', $fiscalNumber));

    return $this;
  }
}
