<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 15:13
 */

namespace EDI\Generator;

use EDI\Generator\Traits\ContactPerson;
use EDI\Generator\Traits\DeliveryTerms;
use EDI\Generator\Traits\NameAndAddress;

/**
 * ORDRSP message (order response / order confirmation) in the ITEK35 profile of UN/EDIFACT D.96B.
 * Header segments are composed in the order of $composeKeys, then the line items, then UNS+S.
 *
 * @link http://www.unece.org/trade/untdid/d96b/trmd/ordrsp_s.htm
 *
 * @package EDI\Generator
 */
class Ordrsp extends Message {
  use ContactPerson,
    DeliveryTerms,
    NameAndAddress;

  /** @var array|null BGM segment with the order confirmation number */
  protected $orderConfirmationNumber;
  /** @var array|null DTM segment, confirmation date (qualifier 4) */
  protected $orderConfirmationDate;
  /** @var array|null DTM segment, delivery date */
  protected $deliveryDate;
  /** @var array|null RFF segment, order number (qualifier VN) */
  protected $orderNumber;
  /** @var array|null optional extra UNS segment in the header, see setPositionSeparator() */
  protected $positionSeparator;
  /** @var array|null FTX segment, order instruction (qualifier ORI) */
  protected $orderInstruction;
  /** @var array|null RFF segment, additional reference number (qualifier ACD) */
  protected $additionalReferenceNumber;
  /** @var array|null RFF segment, transport document number (qualifier AAS) */
  protected $transportDocumentNumber;
  /** @var array|null RFF segment, project number (qualifier AEP) */
  protected $projectNumber;
  /** @var array|null RFF segment, beneficiary's reference (qualifier AFO) */
  protected $beneficiaryReference;
  /** @var array|null RFF segment, second beneficiary's reference (qualifier AFP) */
  protected $beneficiaryReference2;
  /** @var array|null ALC segment, delivery charge or allowance */
  protected $allowanceOrCharge;
  /** @var array|null MOA segment, amount of the allowance or charge (qualifier 8) */
  protected $allowanceOrChargeMoa;
  /** @var array|null TDT segment, transport details */
  protected $transportData;
  /** @var Ordrsp\Item[] line items, composed after the header */
  protected $items = [];
  /** @var string[] property names composed before the line items, in output order */
  protected $composeKeys
  = [
    'orderConfirmationNumber',
    'orderConfirmationDate',
    'deliveryDate',
    'orderNumber',
    'orderInstruction',
    'additionalReferenceNumber',
    'transportDocumentNumber',
    'projectNumber',
    'beneficiaryReference',
    'beneficiaryReference2',
    'manufacturerAddress',
    'manufacturerAddressContactPerson',
    'manufacturerAddressMailAddress',
    'manufacturerAddressPhoneNumber',
    'manufacturerAddressFaxNumber',
    'wholesalerAddress',
    'wholesalerAddressContactPerson',
    'wholesalerAddressMailAddress',
    'wholesalerAddressPhoneNumber',
    'wholesalerAddressFaxNumber',
    'deliveryAddress',
    'deliveryAddressContactPerson',
    'deliveryAddressMailAddress',
    'deliveryAddressPhoneNumber',
    'deliveryAddressFaxNumber',
    'deliveryTerms',
    'allowanceOrCharge',
    'allowanceOrChargeMoa',
    'transportData',
    'positionSeparator',
  ];

  /**
   * Creates an ORDRSP message, by default D.96B UN with association ITEK35.
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
    $identifier = 'ORDRSP',
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
  }


  /**
   * Adds a line item; its segments are composed after the header. Not chainable.
   *
   * @param Ordrsp\Item $item
   *
   * @return void
   */
  public function addItem($item) {
    $this->items[] = $item;
  }

  /**
   * Builds the message: header segments, line items and a closing UNS+S, wrapped in UNH/UNT.
   * Calling it again rebuilds the message from scratch.
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

    $this->messageContent[] = [
      'UNS',
      'S',
    ];
    parent::compose();
    return $this;
  }

  /**
   * Returns the order confirmation number (BGM segment).
   *
   * @return array|null
   */
  public function getOrderConfirmationNumber() {
    return $this->orderConfirmationNumber;
  }


  /**
   * Sets the order confirmation number and document type (BGM segment).
   *
   * @param string $orderConfirmationNumber confirmation number
   * @param string $documentType            document name code, default 231 (purchase order response)
   *
   * @return $this
   */
  public function setOrderConfirmationNumber($orderConfirmationNumber, $documentType = '231') {
    $this->orderConfirmationNumber = [
      'BGM',
      $documentType,
      $orderConfirmationNumber,
    ];
    return $this;
  }

  /**
   * Returns the confirmation date (DTM segment, qualifier 4).
   *
   * @return array|null
   */
  public function getOrderConfirmationDate() {
    return $this->orderConfirmationDate;
  }

  /**
   * Sets the confirmation date (DTM segment, qualifier 4).
   *
   * @param string|\DateTimeInterface $orderConfirmationDate date as "Y-m-d" string or date object
   *
   * @return $this
   * @throws EdifactException if the date is invalid
   */
  public function setOrderConfirmationDate($orderConfirmationDate) {
    $this->orderConfirmationDate = $this->addDTMSegment($orderConfirmationDate, '4');
    return $this;
  }

  /**
   * Returns the delivery date (DTM segment).
   *
   * @return array|null
   */
  public function getDeliveryDate() {
    return $this->deliveryDate;
  }

  /**
   * Sets the delivery date (DTM segment).
   *
   * @param string|\DateTimeInterface $deliveryDate     date as "Y-m-d" string or date object
   * @param int                       $type             DTM qualifier, default 2 (requested delivery date)
   * @param int                       $formatQuantifier EdifactDate format code, default 102 (CCYYMMDD)
   *
   * @return $this
   * @throws EdifactException if the date is invalid
   */
  public function setDeliveryDate(
    $deliveryDate,
    $type = EdifactDate::TYPE_DELIVERY_DATE_REQUESTED,
    $formatQuantifier = EdifactDate::DATE
  ) {
    $this->deliveryDate = $this->addDTMSegment($deliveryDate, $type, $formatQuantifier);
    return $this;
  }

  /**
   * Returns the order number (RFF segment, qualifier VN).
   *
   * @return array|null
   */
  public function getOrderNumber() {
    return $this->orderNumber;
  }

  /**
   * Sets the order number being confirmed (RFF segment, qualifier VN). An empty value sends no segment.
   *
   * @param string $orderNumber order number, max. 35 characters
   *
   * @return $this
   */
  public function setOrderNumber($orderNumber) {
    $this->orderNumber = $this->addRFFSegment('VN', $orderNumber);
    return $this;
  }

  /**
   * Returns the extra header separator (UNS segment).
   *
   * @return array|null
   */
  public function getPositionSeparator() {
    return $this->positionSeparator;
  }

  /**
   * Adds an extra UNS+S separator at the end of the header. compose() always appends
   * one after the line items, so this is only needed for receivers that expect both.
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
   * Returns the order instruction (FTX segment, qualifier ORI).
   *
   * @return array|null
   */
  public function getOrderInstruction() {
    return $this->orderInstruction;
  }

  /**
   * Sets the order instruction (FTX segment, qualifier ORI).
   *
   * @param string $orderInstruction free text, split into lines of 70 characters
   *
   * @return $this
   */
  public function setOrderInstruction($orderInstruction) {
    $this->orderInstruction = self::addFTXSegment($orderInstruction, 'ORI');
    return $this;
  }

  /**
   * Returns the additional reference number (RFF segment, qualifier ACD).
   *
   * @return array|null
   */
  public function getAdditionalReferenceNumber() {
    return $this->additionalReferenceNumber;
  }

  /**
   * Sets an additional reference number (RFF segment, qualifier ACD). An empty value sends no segment.
   *
   * @param string $additionalReferenceNumber reference, max. 35 characters
   *
   * @return $this
   */
  public function setAdditionalReferenceNumber($additionalReferenceNumber) {
    $this->additionalReferenceNumber = self::addRFFSegment('ACD', $additionalReferenceNumber);
    return $this;
  }

  /**
   * Returns the transport document number (RFF segment, qualifier AAS).
   *
   * @return array|null
   */
  public function getTransportDocumentNumber() {
    return $this->transportDocumentNumber;
  }

  /**
   * Sets the transport document number (RFF segment, qualifier AAS). An empty value sends no segment.
   *
   * @param string $transportDocumentNumber reference, max. 35 characters
   *
   * @return $this
   */
  public function setTransportDocumentNumber($transportDocumentNumber) {
    $this->transportDocumentNumber = self::addRFFSegment('AAS', $transportDocumentNumber);
    return $this;
  }

  /**
   * Returns the project number (RFF segment, qualifier AEP).
   *
   * @return array|null
   */
  public function getProjectNumber() {
    return $this->projectNumber;
  }

  /**
   * Sets the project number (RFF segment, qualifier AEP). An empty value sends no segment.
   *
   * @param string $projectNumber reference, max. 35 characters
   *
   * @return $this
   */
  public function setProjectNumber($projectNumber) {
    $this->projectNumber = self::addRFFSegment('AEP', $projectNumber);
    return $this;
  }

  /**
   * Returns the beneficiary's reference (RFF segment, qualifier AFO).
   *
   * @return array|null
   */
  public function getBeneficiaryReference() {
    return $this->beneficiaryReference;
  }

  /**
   * Sets the beneficiary's reference (RFF segment, qualifier AFO). An empty value sends no segment.
   *
   * @param string $beneficiaryReference reference, max. 35 characters
   *
   * @return $this
   */
  public function setBeneficiaryReference($beneficiaryReference) {
    $this->beneficiaryReference = self::addRFFSegment('AFO', $beneficiaryReference);
    return $this;
  }

  /**
   * Returns the second beneficiary's reference (RFF segment, qualifier AFP).
   *
   * @return array|null
   */
  public function getBeneficiaryReference2() {
    return $this->beneficiaryReference2;
  }

  /**
   * Sets a second beneficiary's reference (RFF segment, qualifier AFP). An empty value sends no segment.
   *
   * @param string $beneficiaryReference2 reference, max. 35 characters
   *
   * @return $this
   */
  public function setBeneficiaryReference2($beneficiaryReference2) {
    $this->beneficiaryReference2 = self::addRFFSegment('AFP', $beneficiaryReference2);
    return $this;
  }

  /**
   * Returns the delivery charge or allowance (ALC segment).
   *
   * @return array|null
   */
  public function getAllowanceOrCharge() {
    return $this->allowanceOrCharge;
  }

  /**
   * Sets a delivery charge or allowance (ALC segment, service code DL) with its amount
   * (MOA segment, qualifier 8).
   *
   * @param string|int|float $value amount; positive sends a charge (ALC+C), zero or negative an allowance (ALC+A)
   *
   * @return $this
   */
  public function setAllowanceOrCharge($value) {
    $this->allowanceOrCharge = [
      'ALC',
      floatval($value) > 0 ? 'C' : 'A',
      '',
      '',
      '',
      'DL',
    ];

    $this->allowanceOrChargeMoa = self::addMOASegment('8', abs($value));
    return $this;
  }

  /**
   * Returns the transport details (TDT segment).
   *
   * @return array|null
   */
  public function getTransportData() {
    return $this->transportData;
  }

  /**
   * Sets the transport details (TDT segment).
   *
   * @param string|int $modeOfTransport mode of transport code (C220)
   * @param string|int $stageQualifier  transport stage qualifier (DE 8051), default 13
   *
   * @return $this
   */
  public function setTransportData($modeOfTransport, $stageQualifier = 13) {
    $this->transportData = [
      'TDT',
      $stageQualifier,
      '',
      $modeOfTransport,
    ];

    return $this;
  }
}
