<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Coparn;
use PHPUnit\Framework\TestCase;

final class CoparnTest extends TestCase {
    private function encode(Coparn $coparn) {
        $encoder = new Encoder($coparn->compose(9)->getComposed(), true);
        $encoder->setUNA(":+,? '");
        return $encoder->get();
    }

    public function testCoparn() {
        $coparn = (new Coparn('M1'))
            ->setDTMMessageSendingTime('201701210000')
            ->setBooking('400123456', '0001')
            ->setRFFOrder('TEMPORDER')
            ->setVessel('0002W', 'COS', 'NOE VESSEL', 'XNOE')
            ->setETA('201701210000')
            ->setETD('201701210000')
            ->setPOL('ITGOA')
            ->setPOD('HKHKG')
            ->setFND('HKHKG')
            ->setCarrier('COS')
            ->setContainer('CBHU1234567', '22G1')
            ->setVGM('11495.14', '201701210000')
            ->setTemperature('14.3')
            ->setDangerous(3, 1366)
            ->addDangerous(3, 1993, '23', 'II')
            ->addDangerous(8, 1760, null, 'III')
            ->setOverDimensions(0, 0, 0, 0, 7.5)
            ->setOverDimensions(0, 0, 0, 0, 7.5)
            ->setCargoCategory('GENERAL CARGO');

        $message = $this->encode($coparn);

        $this->assertStringContainsString("BGM+126+M1+9+AB'DTM+137:201701210000:203'RFF+ATX:TEMPORDER'RFF+BN:400123456'", $message);
        $this->assertStringContainsString("EQD+CN+CBHU1234567+22G1:102:5++2+5'RFF+SQ:0001'TMD+3'", $message);
        $this->assertStringContainsString("TMP+2+14.3:CEL'FTX+AAA+++GENERAL CARGO'", $message);
        // packing group is a plain code, not a temperature; '' keeps its position without a flashpoint
        $this->assertStringContainsString("DGS+IMD+3+1366'DGS+IMD+3+1993+23:CEL+II'DGS+IMD+8+1760++III'", $message);
        // a second setOverDimensions() call replaces the DIM segments
        $this->assertSame(1, substr_count($message, 'DIM+13+CMT:::7.5'));
        $this->assertStringNotContainsString('EQN', $message);
    }

    public function testEquipmentQuantityWithoutContainer() {
        $message = $this->encode(
            (new Coparn('M1'))
                ->setDTMMessageSendingTime('201701210000')
                ->setBooking('400123456', '0001')
                ->setEquipmentQuantity(3)
        );

        $this->assertStringContainsString("RFF+BN:400123456'RFF+SQ:0001'EQN+3'TMD+3'TDT+1++3'CNT+16:1'UNT+10+M1'", $message);
    }
}
