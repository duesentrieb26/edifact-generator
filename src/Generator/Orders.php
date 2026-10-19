<?php

/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.01.2018
 * Time: 16:30
 */

namespace EDI\Generator;

use EDI\Generator\Orders\Item;
use EDI\Generator\Traits\ContactPerson;
use EDI\Generator\Traits\NameAndAddress;
use EDI\Generator\Traits\TransportData;


/**
 * ORDERS message (purchase order) in the ITEK35 profile of UN/EDIFACT D.96B.
 * Header segments are composed in the order of $composeKeys, followed by the line items.
 *
 * @link http://www.unece.org/trade/untdid/d96b/trmd/orders_s.htm
 * @package EDI\Generator
 */
class Orders extends Message {
    use ContactPerson,
        NameAndAddress,
        TransportData;

    /** @var array|null BGM segment with the order number */
    protected $orderNumber;
    /** @var array|null DTM segment, order date (qualifier 4) */
    protected $orderDate;
    /** @var array|null DTM segment, requested delivery date (qualifier 2) */
    protected $deliveryDate;
    /** @var array|null RFF segment, collective order number (qualifier ACD) */
    protected $collectiveOrderNumber;
    /** @var array|null RFF segment, internal identifier (qualifier AAS) */
    protected $internalIdentifier;
    /** @var array|null RFF segment, object number (qualifier AEP) */
    protected $objectNumber;
    /** @var array|null RFF segment, object description 1 (qualifier AFO) */
    protected $objectDescription1;
    /** @var array|null RFF segment, object description 2 (qualifier AFP) */
    protected $objectDescription2;
    /** @var array|null FTX segment, order instruction (qualifier ORI) */
    protected $orderDescription;
    /** @var array|null FTX segment, delivery instruction (qualifier DIN) */
    protected $orderNotification;
    /** @var array|null TOD segment, delivery terms */
    protected $deliveryTerms;

    /** @var Item[] line items, composed after the header */
    protected $items;

    /** @var string[] property names composed before the line items, in output order */
    protected $composeKeys = [
        'orderNumber',
        'orderDate',
        'deliveryDate',
        'orderDescription',
        'orderNotification',
        'collectiveOrderNumber',
        'internalIdentifier',
        'objectNumber',
        'objectDescription1',
        'objectDescription2',
        'manufacturerAddress',
        'wholesalerAddress',
        'contactPerson',
        'mailAddress',
        'phoneNumber',
        'faxNumber',
        'deliveryAddress',
        'transportData',
        'deliveryTerms',
    ];


    /**
     * Creates an ORDERS message, by default D.96B UN with association ITEK35.
     *
     * @param string|null $messageId message reference for UNH/UNT, generated when null
     * @param string $identifier message type
     * @param string $version message version number
     * @param string $release message release number
     * @param string $controllingAgency controlling agency
     * @param string $association association assigned code
     */
    public function __construct(
        $messageId = null,
        $identifier = 'ORDERS',
        $version = 'D',
        $release = '96B',
        $controllingAgency = 'UN',
        $association = 'ITEK35'
    ) {
        parent::__construct(
            $identifier,
            $version,
            $release,
            $controllingAgency,
            $messageId,
            $association
        );
        $this->items = [];
    }

    /**
     * Adds a line item; its segments are composed after the header. Not chainable.
     *
     * @param Item $item
     * @return void
     */
    public function addItem($item) {
        $this->items[] = $item;
    }

    /**
     * Builds the message: header segments and line items, wrapped in UNH/UNT.
     * Calling it again rebuilds the message from scratch.
     *
     * @param mixed $msgStatus not used
     * @return $this
     * @throws EdifactException if a header value is invalid or the message is empty
     */
    public function compose($msgStatus = null) {
        $this->messageContent = [];
        $this->composeByKeys();

        foreach ($this->items as $item) {
            $composed = $item->compose();
            foreach ($composed as $entry) {
                $this->messageContent[] = $entry;
            }
        }

        parent::compose();
        return $this;
    }

    /**
     * Returns the order number (BGM segment).
     *
     * @return array|null
     */
    public function getOrderNumber() {
        return $this->orderNumber;
    }

    /**
     * Sets the order number and document type (BGM segment).
     *
     * @param string $orderNumber order number
     * @param string $documentType document name code: 120 (default), 220, 221, 226, 227, 228, 126,
     *                             YA8, YS8, YK8, 248 or 447
     * @return $this
     * @throws EdifactException if the document type is not allowed
     */
    public function setOrderNumber($orderNumber, $documentType = '120') {
        $this->isAllowed($documentType, [
            '120',
            '220',
            '221',
            '226',
            '227',
            '228',
            '126',
            'YA8',
            'YS8',
            'YK8',
            '248',
            '447'
        ]);
        $this->orderNumber = ['BGM', $documentType, $orderNumber];
        return $this;
    }

    /**
     * Returns the order date (DTM segment, qualifier 4).
     *
     * @return array|null
     */
    public function getOrderDate() {
        return $this->orderDate;
    }

    /**
     * Sets the order date (DTM segment, qualifier 4).
     *
     * @param string|\DateTimeInterface $orderDate date as "Y-m-d" string or date object
     * @return $this
     * @throws EdifactException if the date is invalid
     */
    public function setOrderDate($orderDate) {
        $this->orderDate = $this->addDTMSegment($orderDate, '4');
        return $this;
    }

    /**
     * Returns the requested delivery date (DTM segment, qualifier 2).
     *
     * @return array|null
     */
    public function getDeliveryDate() {
        return $this->deliveryDate;
    }

    /**
     * Sets the requested delivery date (DTM segment, qualifier 2).
     *
     * @param string|\DateTimeInterface $deliveryDate date as "Y-m-d" string or date object
     * @return $this
     * @throws EdifactException if the date is invalid
     */
    public function setDeliveryDate($deliveryDate) {
        $this->deliveryDate = $this->addDTMSegment($deliveryDate, '2');
        return $this;
    }

    /**
     * Returns the collective order number (RFF segment, qualifier ACD).
     *
     * @return array|null
     */
    public function getCollectiveOrderNumber() {
        return $this->collectiveOrderNumber;
    }

    /**
     * Sets the collective order number (RFF segment, qualifier ACD). An empty value sends no segment.
     *
     * @param string $collectiveOrderNumber reference, max. 35 characters
     * @return $this
     */
    public function setCollectiveOrderNumber($collectiveOrderNumber) {
        $this->collectiveOrderNumber = $this->addRFFSegment('ACD', $collectiveOrderNumber);
        return $this;
    }

    /**
     * Returns the internal identifier (RFF segment, qualifier AAS).
     *
     * @return array|null
     */
    public function getInternalIdentifier() {
        return $this->internalIdentifier;
    }

    /**
     * Sets the internal identifier (RFF segment, qualifier AAS). An empty value sends no segment.
     *
     * @param string $internalIdentifier reference, max. 35 characters
     * @return $this
     */
    public function setInternalIdentifier($internalIdentifier) {
        $this->internalIdentifier = $this->addRFFSegment('AAS', $internalIdentifier);
        return $this;
    }

    /**
     * Returns the object number (RFF segment, qualifier AEP).
     *
     * @return array|null
     */
    public function getObjectNumber() {
        return $this->objectNumber;
    }

    /**
     * Sets the object (project) number (RFF segment, qualifier AEP). An empty value sends no segment.
     *
     * @param string $objectNumber reference, max. 35 characters
     * @return $this
     */
    public function setObjectNumber($objectNumber) {
        $this->objectNumber = $this->addRFFSegment('AEP', $objectNumber);
        return $this;
    }

    /**
     * Returns the first object description (RFF segment, qualifier AFO).
     *
     * @return array|null
     */
    public function getObjectDescription1() {
        return $this->objectDescription1;
    }

    /**
     * Sets the first object description (RFF segment, qualifier AFO). An empty value sends no segment.
     *
     * @param string $objectDescription1 text, max. 35 characters
     * @return $this
     */
    public function setObjectDescription1($objectDescription1) {
        $this->objectDescription1 = $this->addRFFSegment('AFO', $objectDescription1);
        return $this;
    }

    /**
     * Returns the second object description (RFF segment, qualifier AFP).
     *
     * @return array|null
     */
    public function getObjectDescription2() {
        return $this->objectDescription2;
    }

    /**
     * Sets the second object description (RFF segment, qualifier AFP). An empty value sends no segment.
     *
     * @param string $objectDescription2 text, max. 35 characters
     * @return $this
     */
    public function setObjectDescription2($objectDescription2) {
        $this->objectDescription2 = $this->addRFFSegment('AFP', $objectDescription2);
        return $this;
    }

    /**
     * Returns the order instruction (FTX segment, qualifier ORI).
     *
     * @return array|null
     */
    public function getOrderDescription() {
        return $this->orderDescription;
    }

    /**
     * Sets the order instruction (FTX segment, qualifier ORI).
     *
     * @param string $orderDescription free text, split into lines of 70 characters
     * @return $this
     */
    public function setOrderDescription($orderDescription) {
        $this->orderDescription = self::addFTXSegment($orderDescription, 'ORI');
        return $this;
    }

    /**
     * Returns the delivery instruction (FTX segment, qualifier DIN).
     *
     * @return array|null
     */
    public function getOrderNotification() {
        return $this->orderNotification;
    }

    /**
     * Sets the delivery instruction (FTX segment, qualifier DIN).
     *
     * @param string $orderNotification free text, split into lines of 70 characters
     * @return $this
     */
    public function setOrderNotification($orderNotification) {
        $this->orderNotification = self::addFTXSegment($orderNotification, 'DIN');
        return $this;
    }

    /**
     * Returns the delivery terms (TOD segment).
     *
     * @return array|null
     */
    public function getDeliveryTerms() {
        return $this->deliveryTerms;
    }

    /**
     * Sets the delivery terms (TOD+6 segment).
     *
     * @param string $deliveryTerms terms code: CAF, DDP, DAF, FCA, CAI or ZZZ
     * @return $this
     * @throws EdifactException if the code is not allowed
     */
    public function setDeliveryTerms($deliveryTerms) {
        $this->isAllowed(
            $deliveryTerms,
            ['CAF', 'DDP', 'DAF', 'FCA', 'CAI', 'ZZZ']
        );
        $this->deliveryTerms = ['TOD', '6', '', $deliveryTerms];
        return $this;
    }
}
