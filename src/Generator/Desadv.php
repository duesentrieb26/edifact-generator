<?php

namespace EDI\Generator;

use EDI\Generator\Desadv\Package;
use EDI\Generator\Traits\ContactPerson;
use EDI\Generator\Traits\DeliveryTerms;
use EDI\Generator\Traits\NameAndAddress;
use EDI\Generator\Traits\TransportData;

/**
 * DESADV (despatch advice) message: announces a delivery with its header data,
 * addresses, transport details and the packed goods (CPS/PAC/LIN groups).
 * Defaults to D.96B with the ITEK35 association.
 *
 * @link http://www.unece.org/trade/untdid/d96b/trmd/desadv_s.htm
 * @package EDI\Generator
 */
class Desadv extends Message {
    use ContactPerson,
        NameAndAddress,
        DeliveryTerms,
        TransportData;

    /** Document type 22E: delivery advice */
    const DELIVERY_ADVICE = '22E';
    /** Document type 270: delivery note */
    const DELIVERY_NOTE = '270';
    /** Document type 351: despatch advice */
    const DELIVERY_NOTE_ADVICE = '351';

    /** @var array|null BGM segment with document type and delivery note number */
    protected $deliveryNoteNumber;
    /** @var array|null DTM segment, qualifier 137 (document date) */
    protected $deliveryNoteDate;
    /** @var array|null DTM segment, qualifier 17 (estimated delivery date) */
    protected $shippingDate;
    /** @var array|null DTM segment, qualifier 11 (despatch date) */
    protected $deliveryDate;
    /** @var \EDI\Generator\Desadv\Item[] line items that are not packed in a package */
    protected $items;
    /** @var Package[] packages, each emitted as its own CPS group */
    protected $packages = [];
    /** @var string[] property names composed in this order into the message header */
    protected $composeKeys = [
        'deliveryNoteNumber',
        'deliveryNoteDate',
        'deliveryDate',
        'shippingDate',
        'manufacturerAddress',
        'wholesalerAddress',
        'deliveryAddress',
        'deliveryTerms',
        'contactPerson',
        'mailAddress',
        'phoneNumber',
        'faxNumber',
        'transportData'
    ];

    /**
     * Creates a DESADV message.
     *
     * @param string|null $messageId         message reference (UNH 0062), generated when null
     * @param string      $identifier        message type
     * @param string      $version           message version number
     * @param string      $release           message release number
     * @param string      $controllingAgency controlling agency
     * @param string      $association       association assigned code
     */
    public function __construct(
        $messageId = null,
        $identifier = 'DESADV',
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
        $this->packages = [];
        $this->items = [];
    }

    /**
     * Adds a line item without a package; it is emitted inside the first CPS group.
     *
     * @param \EDI\Generator\Desadv\Item $item
     *
     * @return $this
     */
    public function addItem($item) {
        $this->items[] = $item;

        return $this;
    }


    /**
     * Adds a package with its package items; each package becomes its own CPS group.
     *
     * @param Package $item
     *
     * @return $this
     */
    public function addPackage($item) {
        $this->packages[] = $item;

        return $this;
    }

    /**
     * Sets the document type and delivery note number (BGM segment).
     *
     * @param string $documentType one of DELIVERY_ADVICE (22E), DELIVERY_NOTE (270), DELIVERY_NOTE_ADVICE (351)
     * @param string $number       delivery note number
     *
     * @return $this
     * @throws EdifactException if the document type is not allowed
     */
    public function setDeliveryNoteNumber($documentType, $number) {
        $this->isAllowed($documentType, [
            self::DELIVERY_ADVICE,
            self::DELIVERY_NOTE,
            self::DELIVERY_NOTE_ADVICE
        ]);
        $this->deliveryNoteNumber = ['BGM', $documentType, $number];

        return $this;
    }

    /**
     * Returns the BGM segment with the delivery note number.
     *
     * @return array|null
     */
    public function getDeliverNoteNumber() {
        return $this->deliveryNoteNumber;
    }

    /**
     * Returns the shipping date (DTM segment, qualifier 17).
     *
     * @return array|null
     */
    public function getShippingDate() {
        return $this->shippingDate;
    }

    /**
     * Sets the shipping date (DTM segment, qualifier 17).
     *
     * @param string|\DateTimeInterface $shippingDate date as 'Y-m-d' string or date object
     *
     * @return $this
     * @throws EdifactException if the date is invalid
     */
    public function setShippingDate($shippingDate) {
        $this->shippingDate = $this->addDTMSegment($shippingDate, '17');

        return $this;
    }

    /**
     * Returns the delivery date (DTM segment, qualifier 11).
     *
     * @return array|null
     */
    public function getDeliveryDate() {
        return $this->deliveryDate;
    }

    /**
     * Sets the delivery date (DTM segment, qualifier 11).
     *
     * @param string|\DateTimeInterface $deliveryDate date as 'Y-m-d' string or date object
     *
     * @return $this
     * @throws EdifactException if the date is invalid
     */
    public function setDeliveryDate($deliveryDate) {
        $this->deliveryDate = $this->addDTMSegment($deliveryDate, '11');

        return $this;
    }

    /**
     * Returns the delivery note date (DTM segment, qualifier 137).
     *
     * @return array|null
     */
    public function getDeliveryNoteDate() {
        return $this->deliveryNoteDate;
    }

    /**
     * Sets the delivery note date (DTM segment, qualifier 137).
     *
     * @param string|\DateTimeInterface $deliveryNoteDate date as 'Y-m-d' string or date object
     *
     * @return $this
     * @throws EdifactException if the date is invalid
     */
    public function setDeliveryNoteDate($deliveryNoteDate) {
        $this->deliveryNoteDate = $this->addDTMSegment($deliveryNoteDate, '137');

        return $this;
    }


    /**
     * Builds the message: header segments, the consignment CPS group with total
     * package count and weight, unpacked items, then one CPS group per package.
     *
     * @param mixed $msgStatus unused
     *
     * @return $this
     * @throws EdifactException if a header segment is invalid or the message is empty
     */
    public function compose($msgStatus = null) {
        $this->messageContent = [];
        $this->composeByKeys();

        $totalPackages = count($this->packages);
        $packageNumber = 1;
        $packageItemNumber = 1; //$totalPackages + 2;
        $totalWeight = 0;

        /* Calulate the total weight of all packages */
        foreach ($this->packages as $package) {
            $segment = $package->getPackageWeight();
            if ($segment[0] === 'MEA') {
                $totalWeight += (float)str_replace(',', '.', $segment[3][1]);
            }
        }

        $this->messageContent[] = Package::addCPSSegment($packageNumber);
        $this->messageContent[] = Package::addPACSegment($totalPackages, 'PN');
        $this->messageContent[] = Package::addMEASegment($totalWeight, 'AAE', 'BW');

        // LIN is only allowed inside a CPS group (SG10/SG15), so items without a package belong to CPS+1
        foreach ($this->items as $item) {
            $composed = $item->compose();
            foreach ($composed as $entry) {
                $this->messageContent[] = $entry;
            }
        }

        foreach ($this->packages as $package) {
            $this->messageContent[] = Package::addCPSSegment($packageItemNumber, ++$packageNumber);

            $composed = $package->compose();
            foreach ($composed as $entry) {
                $this->messageContent[] = $entry;
            }
            foreach ($package->getItems() as $packageItem) {
                $composed = $packageItem->compose();

                // $this->messageContent[] = Package::addCPSSegment($packageItemNumber++, $packageNumber);
                foreach ($composed as $entry) {
                    $this->messageContent[] = $entry;
                }
            }
        }

        parent::compose();

        return $this;
    }
}
