<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\EdifactException;
use EDI\Generator\Interchange;
use PHPUnit\Framework\TestCase;

class InterchangeTest extends TestCase {
  public function testInterchange() {

    try {

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
      $this->assertStringContainsString('UNB+UNOC:3+1234567890128:14+4260352060008:14+' . date('ymd') . ':' . date('hi'), $message);
    } catch (EdifactException $e) {
      fwrite(STDOUT, "\n\nINTERCHANGE\n" . $message);
    }
  }
}
