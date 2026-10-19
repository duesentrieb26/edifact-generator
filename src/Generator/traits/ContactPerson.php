<?php

namespace EDI\Generator\Traits;

/**
 * Contact person (CTA segment) with e-mail, phone and fax (COM segments).
 * Used by Desadv, Invoic, Orders and Ordrsp. With a prefix the values are stored
 * per address, e.g. deliveryAddressContactPerson (declared in NameAndAddress).
 *
 * @package EDI\Generator\Traits
 */
trait ContactPerson
{
  /** @var array|null CTA segment */
  protected $contactPerson;
  /** @var array|null COM segment, qualifier EM (e-mail) */
  protected $mailAddress;
  /** @var array|null COM segment, qualifier TE (telephone) */
  protected $phoneNumber;
  /** @var array|null COM segment, qualifier FX (fax) */
  protected $faxNumber;

  /**
   * Returns the contact person (CTA segment), or null if not set.
   *
   * @return array|null
   */
  public function getContactPerson()
  {
    return $this->contactPerson;
  }

  /**
   * Sets the contact person (CTA segment). Empty names are ignored.
   *
   * @param string      $contactPerson name of the contact
   * @param string      $section       contact function code (DE 3139), also used as department code
   * @param null|string $prefix        address property prefix, e.g. 'deliveryAddress' stores it in deliveryAddressContactPerson
   *
   * @return $this
   */
  public function setContactPerson($contactPerson, $section = '', $prefix = null)
  {
    $var = "contactPerson";
    if ($prefix) {
      $var = $prefix . ucfirst($var);
    }

    if (empty($contactPerson)){
      return $this;
    }

    $this->{$var} = [
      'CTA',
      $section,
      [
        $section,
        trim($contactPerson),
      ],
    ];

    return $this;
  }

  /**
   * Returns the e-mail address (COM segment, EM), or null if not set.
   *
   * @return array|null
   */
  public function getMailAddress()
  {
    return $this->mailAddress;
  }

  /**
   * Sets the e-mail address (COM segment, qualifier EM). Empty values are ignored.
   *
   * @param string      $mailAddress e-mail address
   * @param null|string $prefix      address property prefix, e.g. 'deliveryAddress' stores it in deliveryAddressMailAddress
   *
   * @return $this
   */
  public function setMailAddress($mailAddress, $prefix = null)
  {
    $var = "mailAddress";
    if ($prefix) {
      $var = $prefix . ucfirst($var);
    }

    if (empty($mailAddress)){
      return $this;
    }

    $this->{$var} = [
      'COM',
      [
        $mailAddress,
        'EM',
      ],
    ];
    return $this;
  }

  /**
   * Returns the phone number (COM segment, TE), or null if not set.
   *
   * @return array|null
   */
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }

  /**
   * Sets the phone number (COM segment, qualifier TE). Empty values are ignored.
   *
   * @param string      $phoneNumber phone number
   * @param null|string $prefix      address property prefix, e.g. 'deliveryAddress' stores it in deliveryAddressPhoneNumber
   *
   * @return $this
   */
  public function setPhoneNumber($phoneNumber, $prefix = null)
  {
    $var = "phoneNumber";
    if ($prefix) {
      $var = $prefix . ucfirst($var);
    }

    if (empty($phoneNumber)){
      return $this;
    }

    $this->{$var} = [
      'COM',
      [
        $phoneNumber,
        'TE',
      ],
    ];
    return $this;
  }

  /**
   * Returns the fax number (COM segment, FX), or null if not set.
   *
   * @return array|null
   */
  public function getFaxNumber()
  {
    return $this->faxNumber;
  }

  /**
   * Sets the fax number (COM segment, qualifier FX). Empty values are ignored.
   *
   * @param string      $faxNumber fax number
   * @param null|string $prefix    address property prefix, e.g. 'deliveryAddress' stores it in deliveryAddressFaxNumber
   *
   * @return $this
   */
  public function setFaxNumber($faxNumber, $prefix = null)
  {
    $var = "faxNumber";
    if ($prefix) {
      $var = $prefix . ucfirst($var);
    }

    if (empty($faxNumber)){
      return $this;
    }

    $this->{$var} = [
      'COM',
      [
        $faxNumber,
        'FX',
      ],
    ];
    return $this;
  }
}
