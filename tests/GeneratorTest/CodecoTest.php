<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Codeco;
use PHPUnit\Framework\TestCase;

final class CodecoTest extends TestCase {
    public function testCodeco() {
        $container = (new Codeco\Container())
            ->setContainer('CBHU1234567', '22G1', 2, 5)
            ->setBooking('4006531400')
            ->setEffectiveDate('201701020800')
            ->setLocation('DEHAM')
            ->setSeal('1234567', 'CA')
            ->setModeOfTransport(3, 31)
            ->setWeight('G', 15400);

        $codeco = (new Codeco('M1'))
            ->setSenderAndReceiver('ITPIALOMA', 'COSCOS')
            ->setCarrier('COS')
            ->addContainer($container)
            ->compose(9);

        $encoder = new Encoder($codeco->getComposed(), true);
        $encoder->setUNA(":+,? '");
        $message = $encoder->get();

        $this->assertStringContainsString("BGM+34+M1+9'NAD+MS+ITPIALOMA'NAD+MR+COSCOS'NAD+CF+COS:160:166'", $message);
        $this->assertStringContainsString("DTM+7:201701020800:203'LOC+165+DEHAM:139:6'MEA+AAE+G+KGM:15400'", $message);
        $this->assertStringContainsString("TDT+1++3+31'CNT+16:1'UNT+14+M1'", $message);
    }
}
