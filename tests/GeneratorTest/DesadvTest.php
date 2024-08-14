<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 18.01.2018
 * Time: 16:01
 */

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Desadv;
use EDI\Generator\Desadv\Package;
use EDI\Generator\Desadv\PackageItem;
use EDI\Generator\EdifactException;
use EDI\Generator\Interchange;
use Exception;
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
                Desadv::DELIVER_NOTE,
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
                ->setDeliveryNoteNumber(Desadv::DELIVER_NOTE, 'LS123456789')
                ->setDeliveryNoteDate($this->getDateTime())
                ->setDeliveryDate($this->getDateTime())
                ->setShippingDate($this->getDateTime())
                ->setWholesalerAddress(
                    'Name 1',
                    'Name 2',
                    'Name 3',
                    'Street',
                    '99999',
                    'city',
                    'DE'
                )
                ->setContactPerson('John Doe')
                ->setMailAddress('john.doe@company.com')
                ->setPhoneNumber('+49123456789')
                ->setFaxNumber('+49123456789-11')
                ->setDeliveryAddress(
                    'Name 1',
                    'Name 2',
                    'Name 3',
                    'Street',
                    '99999',
                    'city',
                    'DE'
                );




            $desadv->setTransportData(16789, 30, '31S');

            $mainCpsCounter = 1;
            $cpsCounter = 1;
            $totalPackages = 2;
            $pos = 1;
            $package = new Package($mainCpsCounter, $cpsCounter, $totalPackages, 1426.562);
            $package
                ->setPackageQuantity(3, 'CT')
                ->setPackageNumber('00343107380000001051')
                ->setPackageWeight(925.328);


            $packageItem1 = new PackageItem($mainCpsCounter, $cpsCounter, $totalPackages);
            $packageItem1
                ->setPackageContent($pos++, '8290123XX', 'BJ', 3);
            $package->addItem($packageItem1);


            $packageItem2 = new PackageItem($mainCpsCounter, $cpsCounter, $totalPackages);
            $packageItem2
                ->setPackageContent($pos++, '8290123YY', 'BJ', 20);
            $package->addItem($packageItem2);
            $desadv->addPackage($package);


            $package2 = new Package($mainCpsCounter, $cpsCounter);
            $package2
                ->setPackageQuantity(5, 'PN')
                ->setPackageNumber('12345678900001')
                ->setPackageWeight(501.234);


            $packageItem3 = new PackageItem($mainCpsCounter, $cpsCounter, $totalPackages);
            $packageItem3
                ->setPackageContent($pos++, '4250659500284', 'EN', 5);
            $package2->addItem($packageItem3);

            $packageItem4 = new PackageItem($mainCpsCounter, $cpsCounter, $totalPackages);
            $packageItem4
                ->setPackageContent($pos++, '4250659500285', 'EN', 5);
            $package2->addItem($packageItem4);


            $packageItem5 = new PackageItem($mainCpsCounter, $cpsCounter, $totalPackages);
            $packageItem5
                ->setPackageContent($pos++, '4250659500286', 'EN', 5);
            $package2->addItem($packageItem5);


            $package3 = new Package($mainCpsCounter, $cpsCounter);
            $package3
                ->setPackageQuantity(5, 'PN')
                ->setPackageNumber('12345678900002')
                ->setPackageWeight(501.234);


            $packageItem6 = new PackageItem($mainCpsCounter, $cpsCounter, $totalPackages);
            $packageItem6
                ->setPackageContent($pos++, '4250659500287', 'EN', 5);

            $desadv->addPackage($package2);


            $desadv->compose();


            $encoder = new Encoder($interchange->addMessage($desadv)->getComposed(), true);
            $encoder->setUNA(":+,? '");

            $message = str_replace("'", "'\n", $encoder->get());
            fwrite(STDOUT, "\n\nDESADV\n" . $message);

            $this->assertStringContainsString('TDT+13+16789+30+31S', $message);
            $this->assertStringContainsString('DTM+137', $message);
            $this->assertStringContainsString('DTM+11', $message);
            $this->assertStringContainsString('DTM+17', $message);
            $this->assertStringContainsString('CTA++', $message);
            $this->assertStringContainsString('COM+', $message);
            $this->assertStringContainsString('CPS+1', $message);
            $this->assertStringContainsString('CPS+2:1', $message);
            $this->assertStringContainsString('PAC+5:PN', $message);
            $this->assertStringContainsString('GIN+BJ:00343107380000001051', $message);
            $this->assertStringContainsString('GIN+BJ:12345678900001', $message);
            $this->assertStringContainsString('CPS+3:1', $message);
            $this->assertStringContainsString('QTY+12:3', $message);
            $this->assertStringContainsString('LIN+2++8290123YY:BJ::89', $message);
            $this->assertStringContainsString('LIN+3++4250659500284:EN::89', $message);
        } catch (EdifactException $e) {
            fwrite(STDOUT, "\n\nDESADV\n" . $e->getMessage());
            fwrite(STDOUT, "\n\nDESADV\n" . $e->getTraceAsString());
        }
    }
}
