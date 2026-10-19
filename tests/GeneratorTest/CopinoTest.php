<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Copino;
use PHPUnit\Framework\TestCase;

final class CopinoTest extends TestCase {
    private function encode(Copino $copino) {
        $encoder = new Encoder($copino->compose(5)->getComposed(), true);
        $encoder->setUNA(":+,? '");
        return $encoder->get();
    }

    public function testCopino() {
        $message = $this->encode(
            (new Copino('M1'))
                ->setSenderAndReceiver('ME', 'YOU')
                ->setDTM('201204260000')
                ->setTransporter('12000051161000025', 8, '', 'TRUCKER CORP.', 'XA212345', 'JOHN DOE')
                ->setVessel('CARRIER', 'XNOE', 'NOE VESSEL')
                ->setContainer('CBHU1234567', '22G1', '4001234567', '1')
                ->setMeasures('G', 11000)
                ->setPort('ITGOA', 'VTE')
                ->setDestination('HKHKG')
        );

        $this->assertStringContainsString("RFF+XXX:1'TDT+1+12000051161000025+8++TRUCKER CORP.+++XA212345:146::JOHN DOE'", $message);
        $this->assertStringContainsString("LOC+88+ITGOA:139:6+VTE:72:306'DTM+132:201204260000:203'", $message);
        $this->assertStringContainsString("EQD+CN+CBHU1234567+22G1:102:5++2+5'RFF+BN:4001234567'RFF+SQ:1'MEA+AAE+G+KGM:11000'", $message);
        $this->assertStringContainsString("LOC+7+HKHKG:139:6'CNT+16:1'UNT+18+M1'", $message);
    }

    public function testOptionalSegmentsCanBeLeftOut() {
        $message = $this->encode(
            (new Copino('M1'))->setContainer('CBHU1234567', '22G1', '4001234567', '1')
        );

        $this->assertStringContainsString("RFF+XXX:1'GID+1'SGP+CBHU1234567'", $message);
        $this->assertStringContainsString("RFF+SQ:1'CNT+16:1'UNT+10+M1'", $message);
    }
}
