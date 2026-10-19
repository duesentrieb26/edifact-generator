<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\EdifactException;
use EDI\Generator\Interchange;
use PHPUnit\Framework\TestCase;

class InterchangeTest extends TestCase {
  public function testInterchange() {


    $interchange = new Interchange(
      '1234567890128',
      '4260352060008'
    );
    $interchange->setCharset('UNOC')
      ->setCharsetVersion('3')
      ->setSenderQualifier('14')
      ->setReceiverQualifier('14');


    $encoder = new Encoder($interchange->getComposed(), true);
    $encoder->setUNA(":+,? '");

    $message = str_replace("'", "'\n", $encoder->get());
    $this->assertStringContainsString('UNB+UNOC:3+1234567890128:14+4260352060008:14+' . date('ymd') . ':' . date('Hi'), $message);
  }

  public function testGeneratedDefaults() {
    [$unb, $unz] = (new Interchange('SENDER', 'RECEIVER'))->getComposed();

    $this->assertMatchesRegularExpression('/^\d{6}$/', $unb[4][0]);
    $this->assertMatchesRegularExpression('/^\d{4}$/', $unb[4][1]);
    $this->assertMatchesRegularExpression('/^I[0-9A-F]{13}$/', $unb[5]);
    $this->assertSame(['UNZ', 0, $unb[5]], $unz);
  }

  public function testExplicitValuesAndQualifiers() {
    $interchange = (new Interchange('SENDER', 'RECEIVER', '180123', '1000', 'REF1'))
      ->setSenderQualifier('14')
      ->setReceiverQualifier('ZZ');

    $this->assertSame('14', $interchange->getSenderQualifier());
    $this->assertSame('ZZ', $interchange->getReceiverQualifier());
    $this->assertSame([
      ['UNB', ['UNOA', '2'], ['SENDER', '14'], ['RECEIVER', 'ZZ'], ['180123', '1000'], 'REF1'],
      ['UNZ', 0, 'REF1'],
    ], $interchange->getComposed());
  }

  public function testUncomposedMessageThrows() {
    $interchange = (new Interchange('SENDER', 'RECEIVER'))
      ->addMessage((new \EDI\Generator\Invoic())->setInvoiceNumber('RE123'));

    $this->expectException(EdifactException::class);
    $this->expectExceptionMessage('EDI\Generator\Invoic is not composed');
    $interchange->getComposed();
  }
}
