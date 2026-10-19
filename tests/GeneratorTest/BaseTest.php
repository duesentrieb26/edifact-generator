<?php

namespace GeneratorTest;

use ArrayObject;
use EDI\Generator\Base;
use EDI\Generator\EdifactException;
use PHPUnit\Framework\TestCase;

final class BaseTest extends TestCase {

  private function base() {
    return new class extends Base {
      public $composeKeys = ['first', 'second', 'unset'];
      public $first;
      public $second;
      public $unset;

      public function rff($functionCode, $identifier) {
        return $this->addRFFSegment($functionCode, $identifier);
      }
    };
  }

  public function testComposeByKeysAddsSegmentsAndSegmentLists() {
    $base = $this->base();
    $base->first = ['DTM', ['3']];
    $base->second = [['RFF', ['VA', '1']], ['RFF', ['FC', '2']]];

    $this->assertSame([
      ['DTM', ['3']],
      ['RFF', ['VA', '1']],
      ['RFF', ['FC', '2']],
    ], $base->composeByKeys());
  }

  public function testComposeByKeysThrowsOnFalsyValue() {
    $base = $this->base();
    $base->first = [];

    $this->expectException(EdifactException::class);
    $this->expectExceptionMessage('key first returns null');
    $base->composeByKeys();
  }

  public function testComposeByKeysThrowsOnNonArrayValue() {
    $base = $this->base();
    $base->first = new ArrayObject([['RFF']]);

    $this->expectException(EdifactException::class);
    $this->expectExceptionMessage('key first returns no array structure');
    $base->composeByKeys();
  }

  public function testAddKeyToComposeInsertsAfterPosition() {
    $keys = ['first', 'second'];
    $this->base()->addKeyToCompose('between', $keys, 'first');

    $this->assertSame(['first', 'between', 'second'], $keys);
  }

  public function testSenderAndReceiver() {
    $base = $this->base()->setSender('SENDER')->setReceiver('RECEIVER');

    $this->assertSame('SENDER', $base->getSender());
    $this->assertSame('RECEIVER', $base->getReceiver());
  }

  public function testAddRFFSegment() {
    $base = $this->base();

    $this->assertFalse($base->rff('VA', ''));
    $this->assertSame(['RFF', ['VA', str_repeat('A', 35)]], $base->rff('VA', str_repeat('A', 40)));
  }
}
