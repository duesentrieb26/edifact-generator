<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Coprar;
use PHPUnit\Framework\TestCase;

final class CoprarTest extends TestCase {
    private function encode(Coprar $coprar) {
        $encoder = new Encoder($coprar->compose(5, 45)->getComposed(), true);
        $encoder->setUNA(":+,? '");
        return $encoder->get();
    }

    public function testCoprar() {
        $container = (new Coprar\Container())
            ->setContainer('CBHU1234567', '22G1', 2, 5)
            ->setBooking('4006531400')
            ->setPOD('HKHKG')
            ->setFND('HKHKG')
            ->setVGM('11495.14', '201701210000')
            ->setTemperature('14.3')
            ->setDangerous(3, 1366)
            ->addDangerous(3, 1993, null, 'II')
            ->setOverDimensions(0, 0, 0, 0, 7.5)
            ->setOverDimensions(0, 0, 0, 0, 7.5)
            ->setCargoCategory('GENERAL CARGO')
            ->setContainerOperator('COS');

        $coprar = (new Coprar('M1'))
            ->setVessel('0002W', 'COS', 'NOE VESSEL', 'XNOE')
            ->setPort(9, 'ITGOA', 'VTE')
            ->setEta('201701210000')
            ->setEtd('201701210000')
            ->setCarrier('COS')
            ->addContainer($container);

        $message = $this->encode($coprar);

        $this->assertStringContainsString("TDT+20+0002W+1++COS:172:20+++XNOE:103::NOE VESSEL'LOC+9+ITGOA:139:6+VTE:TER:ZZZ'", $message);
        $this->assertStringContainsString("EQD+CN+CBHU1234567+22G1:102:5++2+5'RFF+BN:4006531400'DTM+798:201701210000:203'", $message);
        $this->assertStringContainsString("DGS+IMD+3+1366'DGS+IMD+3+1993++II'NAD+CF+COS:160:20'CNT+16:1'", $message);
        $this->assertSame(1, substr_count($message, 'DIM+13+CMT:::7.5'));
    }

    public function testContainerWithOnlyEquipment() {
        $message = $this->encode(
            (new Coprar('M1'))->addContainer((new Coprar\Container())->setContainer('CBHU1234567', '22G1', 2, 5))
        );

        $this->assertStringContainsString("RFF+XXX:1'EQD+CN+CBHU1234567+22G1:102:5++2+5'CNT+16:1'UNT+6+M1'", $message);
    }
}
