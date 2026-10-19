<?php

namespace EDI\Generator\Traits;

use EDI\Generator\Message;

/**
 * Name and address segments (NAD) for manufacturer, wholesaler, delivery, invoice and
 * representative party, plus their VAT IDs (RFF) and the properties that ContactPerson
 * fills when called with an address prefix. Used by Desadv, Invoic, Orders and Ordrsp.
 *
 * @link http://www.unece.org/trade/untdid/d96b/trsd/trsdnad.htm
 * @package EDI\Generator\Traits
 */
trait NameAndAddress {
  /** @var array|null NAD segment of the manufacturer (SU) */
  protected $manufacturerAddress;
  /** @var array|null CTA segment of the manufacturer (SU) contact */
  protected $manufacturerAddressContactPerson;
  /** @var array|null COM segment (EM) of the manufacturer (SU) contact */
  protected $manufacturerAddressMailAddress;
  /** @var array|null COM segment (TE) of the manufacturer (SU) contact */
  protected $manufacturerAddressPhoneNumber;
  /** @var array|null COM segment (FX) of the manufacturer (SU) contact */
  protected $manufacturerAddressFaxNumber;
  /** @var array|null RFF segment (VA) with the VAT ID of the manufacturer (SU) */
  protected $manufacturerAddressVatId;

  /** @var array|null NAD segment of the wholesaler (WS) */
  protected $wholesalerAddress;
  /** @var array|null CTA segment of the wholesaler (WS) contact */
  protected $wholesalerAddressContactPerson;
  /** @var array|null COM segment (EM) of the wholesaler (WS) contact */
  protected $wholesalerAddressMailAddress;
  /** @var array|null COM segment (TE) of the wholesaler (WS) contact */
  protected $wholesalerAddressPhoneNumber;
  /** @var array|null COM segment (FX) of the wholesaler (WS) contact */
  protected $wholesalerAddressFaxNumber;
  /** @var array|null RFF segment (VA) with the VAT ID of the wholesaler (WS) */
  protected $wholesalerAddressVatId;

  /** @var array|null NAD segment of the delivery address (ST) */
  protected $deliveryAddress;
  /** @var array|null CTA segment of the delivery address (ST) contact */
  protected $deliveryAddressContactPerson;
  /** @var array|null COM segment (EM) of the delivery address (ST) contact */
  protected $deliveryAddressMailAddress;
  /** @var array|null COM segment (TE) of the delivery address (ST) contact */
  protected $deliveryAddressPhoneNumber;
  /** @var array|null COM segment (FX) of the delivery address (ST) contact */
  protected $deliveryAddressFaxNumber;
  /** @var array|null RFF segment (VA) with the VAT ID of the delivery address (ST) */
  protected $deliveryAddressVatId;

  /** @var array|null NAD segment of the invoice address (IV) */
  protected $invoiceAddress;
  /** @var array|null CTA segment of the invoice address (IV) contact */
  protected $invoiceAddressContactPerson;
  /** @var array|null COM segment (EM) of the invoice address (IV) contact */
  protected $invoiceAddressMailAddress;
  /** @var array|null COM segment (TE) of the invoice address (IV) contact */
  protected $invoiceAddressPhoneNumber;
  /** @var array|null COM segment (FX) of the invoice address (IV) contact */
  protected $invoiceAddressFaxNumber;
  /** @var array|null RFF segment (VA) with the VAT ID of the invoice address (IV) */
  protected $invoiceAddressVatId;

  /** @var array[]|null NAD segment of the representative (AB), followed by its RFF segments */
  protected $representativeAddress;
  /** @var array|null CTA segment of the representative (AB) contact */
  protected $representativeAddressContactPerson;
  /** @var array|null COM segment (EM) of the representative (AB) contact */
  protected $representativeAddressMailAddress;
  /** @var array|null COM segment (TE) of the representative (AB) contact */
  protected $representativeAddressPhoneNumber;
  /** @var array|null COM segment (FX) of the representative (AB) contact */
  protected $representativeAddressFaxNumber;
  /** @var array|null unused, the representative VAT ID is added to $representativeAddress */
  protected $representativeAddressVatId;


  /**
   * Builds a NAD segment with party ID, name, street, city, postal code and country.
   *
   * @param string      $name1                first name line, cut to 35 characters
   * @param string      $name2                second name line, omitted when empty
   * @param string      $name3                third name line, omitted when empty
   * @param string      $street               street, split on word boundaries into up to 4 lines of 35 characters
   * @param string      $zipCode              postal code, cut to 9 characters
   * @param string      $city                 city, cut to 35 characters
   * @param string      $countryCode          ISO 3166 country code, cut to 2 characters
   * @param string      $managingOrganisation code list agency of the party ID (DE 3055), e.g. 9 (GS1) or ZZZ (mutually defined)
   * @param string      $type                 party qualifier (DE 3035), e.g. SU, WS, ST, IV, AB
   * @param string|null $sender               party ID; null uses the message sender, '' omits it
   *
   * @return array NAD segment
   * @link http://www.unece.org/trade/untdid/d96b/trsd/trsdnad.htm
   */
  public function addNameAndAddress(
    $name1,
    $name2,
    $name3,
    $street,
    $zipCode,
    $city,
    $countryCode,
    $managingOrganisation,
    $type,
    $sender = ''
  ) {
    if (is_null($sender)) {
      $sender = $this->sender;
    }

    $partyId = '';
    if ($sender) {
      $partyId = [
        self::maxChars($sender),
        '',
        trim($managingOrganisation),
      ];
    }

    $name = [
      self::maxChars($name1),
    ];
    if ($name2) {
      $name[] = self::maxChars($name2);
    }
    if ($name3) {
      $name[] = self::maxChars($name3);
    }

    return [
      'NAD',
      $type,
      $partyId,
      '',
      $name,
      // C059 street has 4 components; city (3164) is a simple element and must not be split
      Message::splitTextOnWordBoundary(trim((string) $street), 35, 4),
      [self::maxChars($city)],
      [
        '',
      ],
      [
        self::maxChars($zipCode, 9),
      ],
      [
        self::maxChars($countryCode, 2),
      ],
    ];
  }

  /**
   * Builds a NAD segment that identifies the party only by its GLN (code list agency 9 = GS1).
   *
   * @param string $gln  global location number (GLN)
   * @param string $type party qualifier (DE 3035), e.g. SU, WS, ST, IV
   *
   * @return array NAD segment
   */
  public function addAddressGln($gln, $type) {
    return [
      'NAD',
      $type,
      [
        $gln,
        '',
        9,
      ],
    ];
  }


  /**
   * Returns the manufacturer address (NAD segment, SU), or null if not set.
   *
   * @return array|null
   */
  public function getManufacturerAddress() {
    return $this->manufacturerAddress;
  }

  /**
   * Returns the wholesaler address (NAD segment, WS), or null if not set.
   *
   * @return array|null
   */
  public function getWholesalerAddress() {
    return $this->wholesalerAddress;
  }

  /**
   * Returns the representative segments (NAD AB followed by its RFF segments), or null if not set.
   *
   * @return array[]|null
   */
  public function getRepresentativeAddress() {
    return $this->representativeAddress;
  }


  /**
   * Sets the manufacturer address as GLN only (NAD segment, SU).
   *
   * @param string $gln global location number (GLN)
   *
   * @return $this
   */
  public function setManufacturerAddressGln($gln) {
    $this->manufacturerAddress = $this->addAddressGln($gln, 'SU');

    return $this;
  }

  /**
   * Sets the wholesaler address as GLN only (NAD segment, WS).
   *
   * @param string $gln global location number (GLN)
   *
   * @return $this
   */
  public function setWholesalerAddressGln($gln) {
    $this->wholesalerAddress = $this->addAddressGln($gln, 'WS');

    return $this;
  }

  /**
   * Sets the delivery address as GLN only (NAD segment, ST).
   *
   * @param string $gln global location number (GLN)
   *
   * @return $this
   */
  public function setDeliveryAddressGln($gln) {
    $this->deliveryAddress = $this->addAddressGln($gln, 'ST');

    return $this;
  }

  /**
   * Sets the invoice address as GLN only (NAD segment, IV).
   *
   * @param string $gln global location number (GLN)
   *
   * @return $this
   */
  public function setInvoiceAddressGln($gln) {
    $this->invoiceAddress = $this->addAddressGln($gln, 'IV');

    return $this;
  }

  /**
   * Returns the delivery address (NAD segment, ST), or null if not set.
   *
   * @return array|null
   */
  public function getDeliveryAddress() {
    return $this->deliveryAddress;
  }

  /**
   * Sets the manufacturer address (NAD segment, SU) and its VAT ID (RFF segment, VA).
   *
   * @param string      $name1                first name line, cut to 35 characters
   * @param string      $name2                second name line, omitted when empty
   * @param string      $name3                third name line, omitted when empty
   * @param string      $street               street, split on word boundaries into up to 4 lines of 35 characters
   * @param string      $zipCode              postal code, cut to 9 characters
   * @param string      $city                 city, cut to 35 characters
   * @param string      $countryCode          ISO 3166 country code, cut to 2 characters
   * @param string      $managingOrganisation code list agency of the party ID (DE 3055), e.g. 9 (GS1) or ZZZ (mutually defined)
   * @param string|null $sender               party ID; null uses the message sender, '' omits it
   * @param string|null $vatId                VAT ID, spaces are removed; omitted when empty
   *
   * @return $this
   */
  public function setManufacturerAddress(
    $name1,
    $name2 = '',
    $name3 = '',
    $street = '',
    $zipCode = '',
    $city = '',
    $countryCode = 'DE',
    $managingOrganisation = 'ZZZ',
    $sender = null,
    $vatId = null
  ) {
    $this->manufacturerAddress = $this->addNameAndAddress(
      $name1,
      $name2,
      $name3,
      $street,
      $zipCode,
      $city,
      $countryCode,
      $managingOrganisation,
      'SU',
      $sender
    );
    if ($vatId) {
      $this->manufacturerAddressVatId = self::addRFFSegment('VA', str_replace(' ', '', $vatId));
    }

    return $this;
  }

  /**
   * Sets the wholesaler address (NAD segment, WS) and its VAT ID (RFF segment, VA).
   *
   * @param string      $name1                first name line, cut to 35 characters
   * @param string      $name2                second name line, omitted when empty
   * @param string      $name3                third name line, omitted when empty
   * @param string      $street               street, split on word boundaries into up to 4 lines of 35 characters
   * @param string      $zipCode              postal code, cut to 9 characters
   * @param string      $city                 city, cut to 35 characters
   * @param string      $countryCode          ISO 3166 country code, cut to 2 characters
   * @param string      $managingOrganisation code list agency of the party ID (DE 3055), e.g. 9 (GS1) or ZZZ (mutually defined)
   * @param string|null $sender               party ID; null uses the message sender, '' omits it
   * @param string|null $vatId                VAT ID, spaces are removed; omitted when empty
   *
   * @return $this
   */
  public function setWholesalerAddress(
    $name1,
    $name2 = '',
    $name3 = '',
    $street = '',
    $zipCode = '',
    $city = '',
    $countryCode = 'DE',
    $managingOrganisation = 'ZZZ',
    $sender = null,
    $vatId = null
  ) {
    $this->wholesalerAddress = $this->addNameAndAddress(
      $name1,
      $name2,
      $name3,
      $street,
      $zipCode,
      $city,
      $countryCode,
      $managingOrganisation,
      'WS',
      $sender
    );

    if ($vatId) {
      $this->wholesalerAddressVatId = self::addRFFSegment('VA', str_replace(' ', '', $vatId));
    }

    return $this;
  }

  /**
   * Adds a representative (NAD segment, AB), followed by its VAT ID (RFF segment, VA).
   *
   * @param string      $name1                first name line, cut to 35 characters
   * @param string      $name2                second name line, omitted when empty
   * @param string      $name3                third name line, omitted when empty
   * @param string      $street               street, split on word boundaries into up to 4 lines of 35 characters
   * @param string      $zipCode              postal code, cut to 9 characters
   * @param string      $city                 city, cut to 35 characters
   * @param string      $countryCode          ISO 3166 country code, cut to 2 characters
   * @param string      $managingOrganisation code list agency of the party ID (DE 3055), e.g. 9 (GS1) or ZZZ (mutually defined)
   * @param string|null $sender               party ID; null uses the message sender, '' omits it
   * @param string|null $vatId                VAT ID, spaces are removed; omitted when empty
   *
   * @return $this
   */
  public function setRepresentativeAddress(
    $name1,
    $name2 = '',
    $name3 = '',
    $street = '',
    $zipCode = '',
    $city = '',
    $countryCode = 'DE',
    $managingOrganisation = 'ZZZ',
    $sender = null,
    $vatId = null
  ) {
    if (!is_array($this->representativeAddress)) {
      $this->representativeAddress = [];
    }

    $this->representativeAddress[] = $this->addNameAndAddress(
      $name1,
      $name2,
      $name3,
      $street,
      $zipCode,
      $city,
      $countryCode,
      $managingOrganisation,
      'AB',
      $sender
    );
    if ($vatId) {
      $this->representativeAddress[] = self::addRFFSegment('VA', str_replace(' ', '', $vatId));
    }

    return $this;
  }

  /**
   * Adds the tax number of the representative (RFF segment, FC) after its address.
   *
   * @param string $taxNumber fiscal number, spaces are removed
   *
   * @return $this
   */
  public function setRepresentativeAddressTaxNumber($taxNumber) {
    $this->representativeAddress[] = self::addRFFSegment('FC', str_replace(' ', '', $taxNumber));

    return $this;
  }

  /**
   * Sets the delivery address (NAD segment, ST) and its VAT ID (RFF segment, VA).
   *
   * @param string      $name1                first name line, cut to 35 characters
   * @param string      $name2                second name line, omitted when empty
   * @param string      $name3                third name line, omitted when empty
   * @param string      $street               street, split on word boundaries into up to 4 lines of 35 characters
   * @param string      $zipCode              postal code, cut to 9 characters
   * @param string      $city                 city, cut to 35 characters
   * @param string      $countryCode          ISO 3166 country code, cut to 2 characters
   * @param string      $managingOrganisation code list agency of the party ID (DE 3055), e.g. 9 (GS1) or ZZZ (mutually defined)
   * @param string|null $sender               party ID; null uses the message sender, '' omits it
   * @param string|null $vatId                VAT ID, spaces are removed; omitted when empty
   *
   * @return $this
   */
  public function setDeliveryAddress(
    $name1,
    $name2 = '',
    $name3 = '',
    $street = '',
    $zipCode = '',
    $city = '',
    $countryCode = 'DE',
    $managingOrganisation = 'ZZZ',
    $sender = null,
    $vatId = null
  ) {
    $this->deliveryAddress = $this->addNameAndAddress(
      $name1,
      $name2,
      $name3,
      $street,
      $zipCode,
      $city,
      $countryCode,
      $managingOrganisation,
      'ST',
      $sender
    );

    if ($vatId) {
      $this->deliveryAddressVatId = self::addRFFSegment('VA', str_replace(' ', '', $vatId));
    }

    return $this;
  }

  /**
   * Sets the invoice address (NAD segment, IV) and its VAT ID (RFF segment, VA).
   *
   * @param string      $name1                first name line, cut to 35 characters
   * @param string      $name2                second name line, omitted when empty
   * @param string      $name3                third name line, omitted when empty
   * @param string      $street               street, split on word boundaries into up to 4 lines of 35 characters
   * @param string      $zipCode              postal code, cut to 9 characters
   * @param string      $city                 city, cut to 35 characters
   * @param string      $countryCode          ISO 3166 country code, cut to 2 characters
   * @param string      $managingOrganisation code list agency of the party ID (DE 3055), e.g. 9 (GS1) or ZZZ (mutually defined)
   * @param string|null $sender               party ID; null uses the message sender, '' omits it
   * @param string|null $vatId                VAT ID, spaces are removed; omitted when empty
   *
   * @return $this
   */
  public function setInvoiceAddress(
    $name1,
    $name2 = '',
    $name3 = '',
    $street = '',
    $zipCode = '',
    $city = '',
    $countryCode = 'DE',
    $managingOrganisation = 'ZZZ',
    $sender = null,
    $vatId = null
  ) {
    $this->invoiceAddress = $this->addNameAndAddress(
      $name1,
      $name2,
      $name3,
      $street,
      $zipCode,
      $city,
      $countryCode,
      $managingOrganisation,
      'IV',
      $sender
    );
    if ($vatId) {
      $this->invoiceAddressVatId = self::addRFFSegment('VA', str_replace(' ', '', $vatId));
    }
    return $this;
  }
}
