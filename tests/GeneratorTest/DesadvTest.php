<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 18.01.2018
 * Time: 16:01
 */

namespace GeneratorTest;

use DateInterval;
use DateTime;
use EDI\Encoder;
use EDI\Generator\Desadv;
use EDI\Generator\Desadv\Package;
use EDI\Generator\Desadv\PackageItem;
use EDI\Generator\EdifactException;
use EDI\Generator\Interchange;
use Exception;
use InvalidArgumentException as GlobalInvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

final class DesadvTest extends TestCase {
    /**
     * Test deliver note number
     */
    public function testDeliverNoteNumber() {
        $desadv = new Desadv();
        try {
            $desadv->setDeliveryNoteNumber(
                Desadv::DELIVERY_NOTE,
                'LS123456789'
            );
        } catch (EdifactException $e) {
        }
        $array = $desadv->getDeliverNoteNumber();
        $this->assertEquals([
            'BGM',
            '270',
            'LS123456789'
        ], $array);
    }

    /**
     * 
     * @return DateTime 
     */
    private function getDateTime() {
        return (new \DateTime())
            ->setDate(2018, 1, 23)
            ->setTime(10, 0, 0);
    }

    /**
     * @throws EdifactException
     */
    public function testDeliverNoteNumberException() {
        $this->expectExceptionMessage('value: XXX is not in allowed values:  [22E, 270, 351] in EDI\Generator\Desadv->setDeliveryNoteNumber');
        (new Desadv())
            ->setDeliveryNoteNumber('XXX', 'LS123456789');
    }

    /**
     * @throws EdifactException
     */
    public function testDeliveryDate() {
        $desadv = new Desadv();
        $desadv->setDeliveryDate($this->getDateTime());

        $this->assertEquals([
            'DTM',
            [
                '11',
                '20180123',
                102
            ]
        ], $desadv->getDeliveryDate());
    }


    /**
     * @throws EdifactException
     */
    public function testDeliveryNoteDate() {
        $desadv = new Desadv();
        $desadv->setDeliveryNoteDate($this->getDateTime());

        $this->assertEquals([
            'DTM',
            [
                137,
                '20180123',
                102
            ]
        ], $desadv->getDeliveryNoteDate());
    }


    /**
     * @throws EdifactException
     */
    public function testShippingDate() {
        $desadv = new Desadv();
        $desadv->setShippingDate($this->getDateTime());

        $this->assertEquals([
            'DTM',
            [
                '17',
                '20180123',
                102
            ]
        ], $desadv->getShippingDate());
    }


    /**
     * 
     * @return void 
     * @throws InvalidArgumentException 
     * @throws ExpectationFailedException 
     */
    public function testContactPerson() {
        $desadv = new Desadv();
        $desadv->setContactPerson('John Doe');

        $this->assertEquals([
            'CTA',
            '',
            ['', 'John Doe']
        ], $desadv->getContactPerson());
    }


    public function testMailAddress() {
        $desadv = new Desadv();
        $desadv->setMailAddress('john.doe@company.com');

        $this->assertEquals([
            'COM',
            [
                'john.doe@company.com',
                'EM'
            ]
        ], $desadv->getMailAddress());
    }



    /**
     * 
     * @return void 
     */
    public function testDesadv() {
        $interchange = new Interchange(
            'UNB-Identifier-Sender',
            'UNB-Identifier-Receiver'
        );
        $interchange->setCharset('UNOC')
            ->setCharsetVersion('3');

        try {
            $desadv = (new Desadv())
                ->setSender('UNB-Identifier-Sender')
                ->setReceiver('RECEIVer')
                ->setDeliveryNoteNumber(Desadv::DELIVERY_NOTE_ADVICE, 'LS123456789')
                ->setDeliveryNoteDate($this->getDateTime())
                ->setDeliveryDate($this->getDateTime())
                ->setShippingDate($this->getDateTime()->add(new DateInterval('P1D')))
                ->setWholesalerAddress(
                    'Name 1 WS',
                    'Name 2 WS',
                    'Name 3 WS',
                    'Street WS',
                    '99999',
                    'city WS',
                    'DE'
                )
                ->setContactPerson('John Doe')
                ->setMailAddress('john.doe@company.com')
                ->setPhoneNumber('+49123456789')
                ->setFaxNumber('+49123456789-11')
                ->setDeliveryAddress(
                    'Name 1 DA',
                    'Name 2 DA',
                    'Name 3 DA',
                    'Street DA',
                    '99999',
                    'city DA',
                    'DE'
                );


            $desadv->setTransportData(16789, 30, '31S');

            $pos = 1;
            $package = new Package();
            $package
                ->setPackageQuantity(3, 'CT')
                ->setPackageNumber('00343107380000001051')
                ->setPackageWeight(925.328);


            $packageItem1 = new PackageItem();
            $packageItem1
                ->setPackageContent($pos++, '8290123XX', 'MF', 3)
                ->setDeliveryNoteNumber('123444', 10, $this->getDateTime()->add(new DateInterval('P10D')))
                ->setOrderNumber('123456789', 10);
            $package->addItem($packageItem1);


            $packageItem2 = new PackageItem();
            $packageItem2
                ->setPackageContent($pos++, '8290123YY', 'EN', 20)
                ->setDeliveryNoteNumber('123444', 20, $this->getDateTime()->add(new DateInterval('P10D')))
                ->setOrderNumber('123456789', 20);
            $package->addItem($packageItem2);
            $desadv->addPackage($package);


            $package2 = new Package();
            $package2
                ->setPackageQuantity(5, 'PN')
                ->setPackageNumber('12345678900001')
                ->setPackageWeight(501.234);


            $packageItem3 = new PackageItem();
            $packageItem3
                ->setPackageType('PG', 1)
                ->setPackageContent($pos++, '4250659500284', 'EN', 5)
                ->setDeliveryNoteNumber('123444', 30, $this->getDateTime()->add(new DateInterval('P10D')))
                ->setOrderNumber('123456789', 30);
            $package2->addItem($packageItem3);

            $packageItem4 = new PackageItem();
            $packageItem4
                ->setPackageContent($pos++, '4250659500285', 'EN', 5)
                ->setDeliveryNoteNumber('123444', 50, $this->getDateTime()->add(new DateInterval('P10D')))
                ->setOrderNumber('123456789', 50);
            $package2->addItem($packageItem4);


            $packageItem5 = new PackageItem();
            $packageItem5
                ->setPackageContent($pos++, '4250659500286', 'EN', 5)
                ->setDeliveryNoteNumber('123444', 60, $this->getDateTime()->add(new DateInterval('P10D')))
                ->setOrderNumber('123456789', 60);
            $package2->addItem($packageItem5);


            $package3 = new Package();
            $package3
                ->setPackageQuantity(5, 'PN')
                ->setPackageNumber('12345678900002')
                ->setPackageWeight(501.234);


            $packageItem6 = new PackageItem();
            $packageItem6
                ->setPackageContent($pos++, '4250659500287', 'EN', 5);

            $desadv->addPackage($package2);

            $desadv->compose();

            $encoder = new Encoder($interchange->addMessage($desadv)->getComposed(), true);
            $encoder->setUNA(":+,? '");

            $message = str_replace("'", "'\n", $encoder->get());
            // fwrite(STDOUT, "\n\nDESADV\n" . $message);

            $this->assertStringContainsString('BGM+351+LS123456789', $message);
            $this->assertStringContainsString('TDT+13+16789+30+31S', $message);
            $this->assertStringContainsString('DTM+137', $message);
            $this->assertStringContainsString('DTM+11', $message);
            $this->assertStringContainsString('DTM+17', $message);
            $this->assertStringContainsString('CTA++', $message);
            $this->assertStringContainsString('COM+', $message);
            $this->assertStringContainsString("CPS+1'\nPAC+2", $message);
            $this->assertStringContainsString('MEA+AAE+BW+KGM:1426,56', $message);
            $this->assertStringContainsString('GIN+BJ+00343107380000001051', $message);
            $this->assertStringContainsString('GIN+BJ+12345678900001', $message);
            $this->assertStringContainsString("CPS+1'\nPAC+2", $message);
            $this->assertStringContainsString("CPS+2+1'\nPAC+3++CT", $message);
            $this->assertStringContainsString("CPS+3+1'\nPAC+5++PN'\nMEA+AAE+BW+KGM:501,23'\nPCI+33E'", $message);
            $this->assertStringContainsString("RFF+AAJ:123444'\nRFF+LI:10'\nDTM+2:20180202:102", $message);
            $this->assertStringContainsString("LIN+1++8290123XX:MF'\nQTY+12:3:PCE'\nRFF+VN:123456789'\nRFF+LI:10'\nRFF+AAJ:123444'\nRFF+LI:10", $message);
            $this->assertStringContainsString("LIN+2++8290123YY:EN'\n", $message);
            $this->assertStringContainsString("LIN+3++4250659500284:EN'\n", $message);
            $this->assertStringContainsString("LIN+5++4250659500286:EN'\nQTY+12:5:PCE'\nRFF+VN:123456789'\nRFF+LI:60'\nRFF+AAJ:123444'\nRFF+LI:60'", $message);
        } catch (EdifactException $e) {
            fwrite(STDOUT, "\n\nDESADV\n" . $e->getMessage());
            fwrite(STDOUT, "\n\nDESADV\n" . $e->getTraceAsString());
        }
    }


    /**
     * 
     * @return void 
     * @throws GlobalInvalidArgumentException 
     */
    public function testAllowedUnitsOnMEASegment() {
        $this->expectExceptionMessage('Invalid unit BMW for package weight. Only these are allowed AAI, ABJ, BW, DI, DP, DW, FN, HT, LN, VW, WD');
        $data = Package::addMEASegment(1426.56, 'AAE', 'BMW', 'KGM');
    }

    /**
     * 
     * @return void 
     * @throws GlobalInvalidArgumentException 
     */
    public function testAllowedDimensionsOnMEASegment() {
        $this->expectExceptionMessage('Invalid dimension AAF for package weight. Only these are allowed AAE');
        $data = Package::addMEASegment(1426.56, 'AAF', 'BW', 'KGM');
    }

    /**
     * 
     * @return void 
     * @throws GlobalInvalidArgumentException 
     */
    public function testAllowedQualifiersOnMEASegment() {
        $this->expectExceptionMessage('Invalid qualifier KMG for package weight. Only these are allowed CMK, CMQ, CMT, DZN, GRM, HLT, KGM, KTM, LTR, MMT, MTK, MTQ, NRL, MTR, PCE, PR, SET, TNE');
        $data = Package::addMEASegment(1426.56, 'AAE', 'BW', 'KMG');
    }
}
