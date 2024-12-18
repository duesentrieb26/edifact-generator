<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 18.01.2018
 * Time: 16:01
 */

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\EdifactDate;
use EDI\Generator\EdifactException;
use EDI\Generator\Interchange;
use EDI\Generator\Invoic;
use EDI\Generator\Orders;
use EDI\Generator\Ordrsp;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class OrdersTest extends TestCase {

  public function testDTMPosition() {
    $interchange = (new Interchange(
      'UNB-Identifier-Sender',
      'UNB-Identifier-Receiver'
    ))
      ->setCharset('UNOC')
      ->setSenderQualifier(14)
      ->setReceiverQualifier(14)
      ->setCharsetVersion('3');
    $orderResponse = new Ordrsp();

    try {
      $item = new Ordrsp\Item();
      $item
        ->setPosition(1, 'articleId')
        ->setQuantity(5)
        ->setDeliveryNoteNumber("test")
        ->setOrderNumberWholesaler('500.MY I-50311046-005')
        ->setDeliveryNoteDate('2021-04-20', EdifactDate::TYPE_DELIVERY_DATE_ESTIMATED)
      ;
      $orderResponse->addItem($item);
      $orderResponse->compose();
    } catch (EdifactException $e) {
      fwrite(STDOUT, "\n\nORDERSP\n" . $e->getMessage());
    }

    $encoder = new Encoder($interchange->addMessage($orderResponse)->getComposed(), true);
    $message = str_replace("'", "'\n", $encoder->get());
    $this->assertStringContainsString(
      "QTY+12:5:PCE'\n" .
        "RFF+VN:500.MY I-50311046-005'\n" .
        "RFF+AAJ:test'\n" .
        "DTM+17:20210420:102",
      $message
    );
    $this->assertStringContainsString("UNT+8+", $message);
  }


  /**
   * Test Orders
   * 
   * @throws EdifactException
   * @return void 
   */
  public function testOrders() {
    $interchange = new Interchange(
      'UNB-Identifier-Sender',
      'UNB-Identifier-Receiver'
    );
    $interchange
      ->setCharset('UNOC')
      ->setCharsetVersion('3');
    $orders = new Orders();

    try {
      $orders
        ->setOrderNumber('AB76104')
        ->setOrderDescription('OrderDescription')
        ->setCollectiveOrderNumber('Collective1234')
        ->setInternalIdentifier('processId:1234')
        ->setObjectNumber('objectNumber1234')
        ->setObjectDescription1('objectDescription1')
        ->setObjectDescription2('objectDescription2')
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
        )
        ->setTransportData('TrackingCode1234')
        ->setDeliveryTerms('CAF');

      $item = new Orders\Item();
      $item
        ->setPosition(
          '1',
          '8290123'
        )
        ->setQuantity('3')
        ->setOrderNumberWholesaler('MyOrderNumber')
        ->setAdditionalText('this is an additional text information for article');
      $orders->addItem($item);
      $orders->compose();


      $encoder = new Encoder($interchange->addMessage($orders)->getComposed(), true);
      $encoder->setUNA(":+,? '");

      $message = str_replace("'", "'\n", $encoder->get());

      $this->assertStringContainsString('UNT+20', $message);
    } catch (EdifactException $e) {
      fwrite(STDOUT, "\n\nORDERS\n" . $e->getMessage());
    }
  }


  /**
   * 
   * @return void 
   * @throws InvalidArgumentException 
   * @throws ExpectationFailedException 
   */
  public function testFreeText() {
    $this->assertEquals(
      'FTX+ORI++HAE::89+Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commo:do ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et ma:gnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, :ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat mas:sa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate \'',
      (new Encoder([
        Orders::addFTXSegment(
          'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet.',
          'ORI',
          'HAE'
        )
      ]))->get()
    );


    $x = [
      'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commo',
      'do ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et ma',
      'gnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ',
      'ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat mas',
      'sa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate '
    ];
  }
}
