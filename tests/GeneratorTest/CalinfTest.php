<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Calinf;
use PHPUnit\Framework\TestCase;

final class CalinfTest extends TestCase {
    public function testCalinf() {
        $calinf = (new Calinf('M1'))
            ->setDTMMessageSendingTime('201701210000')
            ->setMessageSender('AGENT', 'AGENT NAME')
            ->setMessageReceiver('TERM', 'TERMINAL')
            ->setVessel('0002W', 'COS', '9300001', 'NOE VESSEL', 'XNOE')
            ->setEta('201701210000')
            ->setEtd('201701220000')
            ->compose();

        $encoder = new Encoder($calinf->getComposed(), true);
        $encoder->setUNA(":+,? '");
        $message = $encoder->get();

        $this->assertStringContainsString("BGM+96+M1+5'DTM+137:201701210000:203'NAD+MS+AGENT+AGENT NAME'NAD+MR+TERM+TERMINAL'", $message);
        $this->assertStringContainsString("DTM+133:201701220000:203'RFF+VM:XNOE'UNT+10+M1'", $message);
    }
}
