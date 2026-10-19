<?php

namespace GeneratorTest;

use EDI\Generator\Base;
use EDI\Generator\Traits\ContactPerson;
use EDI\Generator\Traits\NameAndAddress;
use PHPUnit\Framework\TestCase;

final class ContactPersonTest extends TestCase {

  private function contact() {
    // NameAndAddress declares the prefixed properties (e.g. deliveryAddressContactPerson)
    return new class extends Base {
      use ContactPerson, NameAndAddress;

      public function get($property) {
        return $this->{$property};
      }
    };
  }

  public function testPhoneAndFaxNumber() {
    $contact = $this->contact()
      ->setPhoneNumber('+49 123')
      ->setFaxNumber('+49 124');

    $this->assertEquals(['COM', ['+49 123', 'TE']], $contact->getPhoneNumber());
    $this->assertEquals(['COM', ['+49 124', 'FX']], $contact->getFaxNumber());
  }

  public function testEmptyValuesAreIgnored() {
    $contact = $this->contact()
      ->setContactPerson('')
      ->setMailAddress('')
      ->setPhoneNumber(null)
      ->setFaxNumber('');

    $this->assertNull($contact->getContactPerson());
    $this->assertNull($contact->getMailAddress());
    $this->assertNull($contact->getPhoneNumber());
    $this->assertNull($contact->getFaxNumber());
  }

  public function testPrefixStoresOnAddressProperty() {
    $contact = $this->contact()
      ->setContactPerson(' Jane Doe ', 'IC', 'deliveryAddress')
      ->setMailAddress('jane@example.com', 'deliveryAddress')
      ->setPhoneNumber('+49 123', 'deliveryAddress')
      ->setFaxNumber('+49 124', 'deliveryAddress');

    $this->assertEquals(['CTA', 'IC', ['IC', 'Jane Doe']], $contact->get('deliveryAddressContactPerson'));
    $this->assertEquals(['COM', ['jane@example.com', 'EM']], $contact->get('deliveryAddressMailAddress'));
    $this->assertEquals(['COM', ['+49 123', 'TE']], $contact->get('deliveryAddressPhoneNumber'));
    $this->assertEquals(['COM', ['+49 124', 'FX']], $contact->get('deliveryAddressFaxNumber'));
    $this->assertNull($contact->getContactPerson());
    $this->assertNull($contact->getMailAddress());
  }
}
