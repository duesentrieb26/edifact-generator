<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\EdifactException;
use EDI\Generator\Interchange;
use EDI\Generator\Invoic;
use EDI\Generator\Invoic\Item;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Class InvoiceItemTest
 *
 * @package GeneratorTest
 */
class InvoiceItemTest extends TestCase {

  /**
   *
   */
  public function testDiscount() {
    $interchange = (new Interchange(
      'UNB-Identifier-Sender',
      'UNB-Identifier-Receiver'
    ))
      ->setCharset('UNOC')
      ->setCharsetVersion('3');

    $invoice = new Invoic();
    $message = "";
    $invoice
      ->setInvoiceNumber('INV12345');
    $item = new Item();
    $item
      ->setPosition(2, 'articleId')
      ->setGrossPrice(385)
      ->setNetPrice(354.78)
      ->setOrderNumberWholesaler('4501532449')
      ->setDeliveryNoteNumber(931551, "2021-04-19")
      ->setOrderDate("2021-04-10")
      ->setOrderPosition(1000)
      ->addDiscount(-5, Item::DISCOUNT_TYPE_PERCENT, 385, 'LKW Lieferung', 'TD')
      ->addDiscount(-3, Item::DISCOUNT_TYPE_PERCENT, 385, 'Sonderrabatt', 'TD')
      ->addDiscountFactor(354.78, 385);

    $invoice->addItem($item);
    $invoice->compose();
    $encoder = new Encoder($interchange->addMessage($invoice)->getComposed(), true);
    $encoder->setUNA(":+,? '");
    $message = str_replace("'", "'\n", $encoder->get());
    $this->assertStringContainsString(
      "PRI+NTP:354,78:::1:PCE'\n" .
        "PRI+GRP:385,00:::1:PCE'\n" .
        "RFF+VN:4501532449'\n" .
        "DTM+4:20210410:102'\n" .
        "RFF+LI:1000'\n" .
        "RFF+AAJ:931551'\n" .
        "DTM+2:20210419:102'\n",
      $message
    );
    $this->assertStringContainsString("ALC+A++++TD:::LKW Lieferung'\nPCD+3:5,00'\nMOA+8:19,25", $message);
    $this->assertStringContainsString("ALC+A++++TD:::Sonderrabatt'\nPCD+3:3,00'\nMOA+8:11,55", $message);
    $this->assertStringContainsString("ALC+A++++SF'\nPCD+1:0,9215'\nMOA+8:30,22", $message);
  }

  /**
   * 
   */
  public function testSpecialChars() {
    $interchange = (new Interchange(
      'UNB-Identifier-Sender',
      'UNB-Identifier-Receiver'
    ))
      ->setCharset('UNOC')
      ->setCharsetVersion('3');

    $invoice = new Invoic();
    $message = "";
    $invoice
      ->setInvoiceNumber('INV12345');
    $item = new Item();
    $item
      ->setPosition(2, 'articleId')
      ->setSpecificationText("JPSRR Typ 1000 B montiert (Ø850)")
      ->setGrossPrice(385)
      ->setNetPrice(354.78)
      ->setOrderNumberWholesaler('4501532449')
      ->addDiscountFactor(354.78, 385);

    $invoice->addItem($item);
    $invoice->compose();
    $encoder = new Encoder($interchange->addMessage($invoice)->getComposed(), true);
    $encoder->setUNA(":+,? '");
    $message = str_replace("'", "'\n", $encoder->get());
    $this->assertStringContainsString("IMD+++SP:::JPSRR Typ 1000 B montiert (850)", $message);
  }


  /**
   * Preis
   */
  public function testPrice() {
    $this->assertEquals(
      'PRI+NTP:25,00:::1:PCE\'',
      (new Encoder(
        [
          Item::addPRISegment('NTP', '25,00'),
        ]
      ))->get()
    );
  }

  /**
   *
   */
  public function testAdditionalProductInformation() {
    $this->assertEquals(
      'PIA+1+555:EN\'',
      (new Encoder(
        [
          Item::addPIASegment('555'),
        ]
      ))->get()
    );
  }

  /**
   * 
   * @return void 
   * @throws InvalidArgumentException 
   * @throws ExpectationFailedException 
   */
  public function testGetPosition() {
    $item = new Item();
    $item->setPosition(
      '1',
      '8290123'
    );

    $expected = [
      'LIN',
      '1',
      '',
      [
        '8290123',
        'MF',
      ],
    ];
    $result = $item->getPosition();

    $this->assertEquals($expected, $result);
  }

  /**
   * @throws \EDI\Generator\EdifactException
   */
  public function testGetQuantity() {
    $item = new Item();
    $item->setQuantity(
      '1'
    );

    $expected = [
      'QTY',
      [
        '12',
        1,
        'PCE',
      ],
    ];
    $result = $item->getQuantity();
    $this->assertEquals($expected, $result);
  }

  /**
   *
   */
  public function XtestGetAdditionalText() {
    $item = (new Item())
      ->setAdditionalText('Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, ali');

    $this->assertEquals([
      'Lorem ipsum dolor sit amet, consectetuer',
      ' adipiscing elit. Aenean commodo ligula ',
      'eget dolor. Aenean massa. Cum sociis nat',
      'oque penatibus et magnis dis parturient ',
      'montes, nascetur ridiculus mus. Donec qu',
      'am felis, ultricies nec, pellentesque eu',
      ', pretium quis, sem. Nulla consequat mas',
      'sa quis enim. Donec pede justo, fringill',
    ], $item->getAdditionalText());

    $composed = $item->compose();
    $this->assertEquals(8, count($composed));
    $this->assertEquals([
      'IMD',
      '',
      '',
      'ZU',
      '',
      '89',
      'am felis, ultricies nec, pellentesque eu'
    ], $composed[5]);
  }

  /**
   * @ignore
   */
  public function XtestGetSpecificationText() {
    $item = (new Item())
      ->setSpecificationText('Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, ali');

    $this->assertEquals([
      'Lorem ipsum dolor sit amet, consectetuer',
      ' adipiscing elit. Aenean commodo ligula ',
    ], $item->getSpecificationText());

    $composed = $item->compose();
    $this->assertEquals(2, count($composed));
    $this->assertEquals([
      'IMD',
      '',
      '',
      'ZU',
      '89',
      ' adipiscing elit. Aenean commodo ligula '
    ], $composed[1]);
  }


  public function XtestIMDSegment() {
    $line = 'IMD+++SP:::12345678901234567890123456789012345:12345\'';
    $encoder = new Encoder(\EDI\Generator\Traits\Item::addIMDSegment('12345678901234567890123456789012345'), true);
    $this->assertEquals(
      $line,
      $encoder->get()
    );
  }

  public function testQuantityKeepsDecimals() {
    $this->assertEquals(['QTY', ['12', 5, 'PCE']], (new Item())->setQuantity(5)->getQuantity());
    $this->assertEquals(['QTY', ['12', '2,500', 'KGM']], (new Item())->setQuantity(2.5, 'KGM')->getQuantity());
  }

  public function testAbsoluteDiscountUsesTheAmount() {
    $this->assertEquals([
      ['ALC', 'A', '', '', '', ['DI']],
      ['MOA', ['8', '5,00']],
    ], (new Item())->addDiscount(-5, Item::DISCOUNT_TYPE_ABSOLUTE, 200)->compose());
  }

  public function testSetTotalKeepsDiscounts() {
    $item = (new Item())
      ->setPosition(1, 'articleId')
      ->addDiscount(-2, Item::DISCOUNT_TYPE_PERCENT, 100)
      ->setTotal(98)
      ->setTotal(100, 125);

    $this->assertEquals([
      ['LIN', 1, '', ['articleId', 'MF']],
      ['ALC', 'A', '', '', '', ['SF']],
      ['PCD', ['3', '2,00']],
      ['MOA', ['8', '2,00']],
      ['MOA', [8, '98,00']],
      ['MOA', [125, '100,00']],
    ], $item->compose());
  }

  public function testUmlautsAreTransliterated() {
    $this->assertEquals(
      [['IMD', '', '', ['ZU', '', '', 'Groesse Oelfilter fuer Kaese']]],
      (new Item())->setAdditionalText('Größe Ölfilter für Käse')->getAdditionalText()
    );
  }
}
