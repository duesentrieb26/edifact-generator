<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Westim;
use PHPUnit\Framework\TestCase;

final class WestimTest extends TestCase {
    private function encode(Westim $westim) {
        $encoder = new Encoder($westim->compose()->getComposed(), true);
        $encoder->setUNA(":+,? '");
        return $encoder->get();
    }

    public function testWestim() {
        $damage = (new Westim\Damage())
            ->setDamage('01', 'IXXX', 'TFA', 'DB', 'SK')
            ->setWork('SC', '', '0', '1', '', '1')
            ->setCost('0', '82.63', 'O', '18.00');

        $westim = (new Westim('ESTNUMBER'))
            ->setTransactionDate('170702')
            ->setCurrency('EUR')
            ->setLabourRate('100.00')
            ->setPartners('IT888XXXX', 'CARRIER')
            ->setContainer('CBHU', '1234567', '4510')
            ->setFullEmpty('F')
            ->addDamage($damage)
            ->setCostTotals('O', '4.5', '82.63', '0', '0', '87.13')
            ->setTotalMessageAmounts('87.13', '80.00', '22');

        $message = $this->encode($westim);

        $this->assertStringContainsString("DTM+ATR+170702'RFF+EST+ESTNUMBER+170702'ACA+EUR+STD:0'LBR+100.00'", $message);
        $this->assertStringContainsString("EQF+CON+CBHU:1234567+4510+MGW:0:KGM'CUI+++F'ECI+D'DAM+01+IXXX+TFA+DB+SK'", $message);
        $this->assertStringContainsString("CTO+O+4.5+82.63+0+0+87.13'TMA+87.13+++++80.00++22'UNT+16+ESTNUMBER'", $message);
    }

    public function testTotalMessageAmountsWithoutOptionalValues() {
        $westim = (new Westim('E'))->setTransactionDate('170702');

        $this->assertStringContainsString("TMA+87.13'", $this->encode($westim->setTotalMessageAmounts('87.13')));
        $this->assertStringContainsString("TMA+87.13+++++++22'", $this->encode($westim->setTotalMessageAmounts('87.13', null, '22')));
    }
}
