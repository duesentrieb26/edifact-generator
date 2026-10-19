<?php

namespace GeneratorTest;

use EDI\Generator\Base;
use EDI\Generator\Traits\NameAndAddress;
use PHPUnit\Framework\TestCase;

final class NameAndAddressTest extends TestCase {

  private function address() {
    return new class extends Base {
      use NameAndAddress;

      public function get($property) {
        return $this->{$property};
      }
    };
  }

  public function testGlnAddresses() {
    $address = $this->address()
      ->setManufacturerAddressGln('4000000000001')
      ->setWholesalerAddressGln('4000000000002')
      ->setDeliveryAddressGln('4000000000003')
      ->setInvoiceAddressGln('4000000000004');

    $this->assertEquals(['NAD', 'SU', ['4000000000001', '', 9]], $address->getManufacturerAddress());
    $this->assertEquals(['NAD', 'WS', ['4000000000002', '', 9]], $address->getWholesalerAddress());
    $this->assertEquals(['NAD', 'ST', ['4000000000003', '', 9]], $address->getDeliveryAddress());
    $this->assertEquals(['NAD', 'IV', ['4000000000004', '', 9]], $address->get('invoiceAddress'));
  }

  public function testWholesalerAddress() {
    $address = $this->address()->setWholesalerAddress(
      'Name 1',
      'Name 2',
      '',
      'Street 1',
      '12345',
      'City',
      'DE',
      'ZZZ',
      '4000000000001'
    );

    $this->assertEquals([
      'NAD',
      'WS',
      ['4000000000001', '', 'ZZZ'],
      '',
      ['Name 1', 'Name 2'],
      ['Street 1'],
      ['City'],
      [''],
      ['12345'],
      ['DE'],
    ], $address->getWholesalerAddress());
  }

  public function testVatIds() {
    $address = $this->address()
      ->setManufacturerAddress('SU', vatId: 'DE 111')
      ->setWholesalerAddress('WS', vatId: 'DE 222')
      ->setDeliveryAddress('ST', vatId: 'DE 333')
      ->setInvoiceAddress('IV', vatId: 'DE 444');

    $this->assertEquals(['RFF', ['VA', 'DE111']], $address->get('manufacturerAddressVatId'));
    $this->assertEquals(['RFF', ['VA', 'DE222']], $address->get('wholesalerAddressVatId'));
    $this->assertEquals(['RFF', ['VA', 'DE333']], $address->get('deliveryAddressVatId'));
    $this->assertEquals(['RFF', ['VA', 'DE444']], $address->get('invoiceAddressVatId'));
  }

  public function testRepresentativeAddressCollectsSegments() {
    $address = $this->address()
      ->setRepresentativeAddress('Representative', vatId: 'DE 555')
      ->setRepresentativeAddressTaxNumber('12/345 678');

    $segments = $address->getRepresentativeAddress();

    $this->assertCount(3, $segments);
    $this->assertSame(['NAD', 'AB'], array_slice($segments[0], 0, 2));
    $this->assertEquals(['RFF', ['VA', 'DE555']], $segments[1]);
    $this->assertEquals(['RFF', ['FC', '12/345678']], $segments[2]);
  }
}
