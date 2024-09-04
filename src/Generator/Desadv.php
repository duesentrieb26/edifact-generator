<?php

namespace EDI\Generator;

use EDI\Generator\Desadv\Package;
use EDI\Generator\Traits\ContactPerson;
use EDI\Generator\Traits\NameAndAddress;
use EDI\Generator\Traits\TransportData;

/**
 * Class Desadv
 * @url http://www.unece.org/trade/untdid/d96b/trmd/desadv_s.htm
 * @package EDI\Generator
 */
class Desadv extends Message {
    use ContactPerson,
        NameAndAddress,
        TransportData;

    const DELIVERY_ADVICE = '22E';
    const DELIVERY_NOTE = '270';
    const DELIVERY_NOTE_ADVICE = '351';

    /** @var array */
    protected $deliveryNoteNumber;
    /** @var array */
    protected $deliveryNoteDate;
    /** @var array */
    protected $shippingDate;
    /** @var array */
    protected $deliveryDate;
    /** @var Item[] */
    protected $items;
    /** @var Package[] */
    protected $packages = [];
    /** @var array  */
    protected $composeKeys = [
        'deliveryNoteNumber',
        'deliveryNoteDate',
        'deliveryDate',
        'shippingDate',
        'manufacturerAddress',
        'contactPerson',
        'mailAddress',
        'phoneNumber',
        'faxNumber',
        'wholesalerAddress',
        'deliveryAddress',
        'transportData'
    ];

    /**
     * Desadv constructor.
     * @param null $messageId
     * @param string $identifier
     * @param string|null $version
     * @param string|null $release
     * @param string|null $controllingAgency
     * @param string|null $association
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
     * @param $item Item
     */
    public function addItem($item) {
        $this->items[] = $item;

        return $this;
    }


    /**
     * @param $item Package
     */
    public function addPackage($item) {
        $this->packages[] = $item;

        return $this;
    }

    /**
     * Set deliver note number
     * @param string $documentType
     * @param $number
     * @return $this
     * @throws EdifactException
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
     * @return array
     */
    public function getDeliverNoteNumber() {
        return $this->deliveryNoteNumber;
    }

    /**
     * @return array
     */
    public function getShippingDate() {
        return $this->shippingDate;
    }

    /**
     * @param string|\DateTime $shippingDate
     * @return $this
     * @throws EdifactException
     */
    public function setShippingDate($shippingDate) {
        $this->shippingDate = $this->addDTMSegment($shippingDate, '17');

        return $this;
    }

    /**
     * @return array
     */
    public function getDeliveryDate() {
        return $this->deliveryDate;
    }

    /**
     * @param string|\DateTime $deliveryDate
     * @return $this
     * @throws EdifactException
     */
    public function setDeliveryDate($deliveryDate) {
        $this->deliveryDate = $this->addDTMSegment($deliveryDate, '11');

        return $this;
    }

    /**
     * @return array
     */
    public function getDeliveryNoteDate() {
        return $this->deliveryNoteDate;
    }

    /**
     * @param string|\DateTime $deliveryNoteDate
     * @return $this
     * @throws EdifactException
     */
    public function setDeliveryNoteDate($deliveryNoteDate) {
        $this->deliveryNoteDate = $this->addDTMSegment($deliveryNoteDate, '137');

        return $this;
    }


    /**
     * @param null $msgStatus
     * @return $this
     * @throws EdifactException
     */
    public function compose($msgStatus = null) {
        $this->composeByKeys();

        foreach ($this->items as $item) {
            $composed = $item->compose();
            foreach ($composed as $entry) {
                $this->messageContent[] = $entry;
            }
        }

        $totalPackages = count($this->packages);
        $packageNumber = 2;
        $packageItemNumber = $totalPackages + 2;
        $totalWeight = 0;

        /* Calulate the total weight of all packages */
        foreach ($this->packages as $package) {
            $segment = $package->getPackageWeight();
            if ($segment[0] === 'MEA') {
                $totalWeight += (float)str_replace(',', '.', $segment[1][3]);
            }
        }

        $this->messageContent[] = Package::addCPSSegment(1);
        $this->messageContent[] = Package::addPACSegment($totalPackages, 'PG');
        $this->messageContent[] = Package::addMEASegment($totalWeight, 'AAE', 'BW');

        foreach ($this->packages as $package) {
            $this->messageContent[] = Package::addCPSSegment(1, $packageNumber);
            $composed = $package->compose();
            foreach ($composed as $entry) {
                $this->messageContent[] = $entry;
            }
            foreach ($package->getItems() as $packageItem) {
                $composed = $packageItem->compose();

                $this->messageContent[] = Package::addCPSSegment($packageNumber, $packageItemNumber++);
                foreach ($composed as $entry) {
                    $this->messageContent[] = $entry;
                }
            }
            $packageNumber++;
        }

        parent::compose();

        return $this;
    }
}
