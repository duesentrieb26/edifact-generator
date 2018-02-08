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
 * Class Orders
 * @url http://www.unece.org/trade/untdid/d96b/trmd/orders_s.htm
 * @package EDI\Generator
 */
class Orders extends Message
{
    use ContactPerson,
        NameAndAddress,
        TransportData
        ;

    /** @var array */
    protected $orderNumber;
    /** @var array */
    protected $orderDate;
    /** @var array */
    protected $deliveryDate;
    /** @var array */
    protected $collectiveOrderNumber;
    /** @var array */
    protected $internalIdentifier;
    /** @var array */
    protected $objectNumber;
    /** @var array */
    protected $objectDescription1;
    /** @var array */
    protected $objectDescription2;
    /** @var array */
    protected $orderDescription;
    /** @var array */
    protected $orderNotification;
    /** @var array */
    protected $deliveryTerms;

    /** @var array */
    protected $items;

    protected $composeKeys = [
        'orderNumber',
        'orderDate',
        'deliveryDate',
        'orderDescription',
        'orderNotification',
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
     * Orders constructor.
     * @param null $messageId
     * @param string $identifier
     * @param string $version
     * @param string $release
     * @param string $controllingAgency
     * @param string $association
     */
    public function __construct(
        $messageId = null,
        $identifier = 'ORDERS',
        $version = 'D',
        $release = '96B',
        $controllingAgency = 'UN',
        $association = 'ITEK35'
    )
    {
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
     * @param $item Item
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    /**
     * @param null $msgStatus
     * @return $this
     * @throws EdifactException
     */
    public function compose($msgStatus = null)
    {
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
     * @return array
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     * @param string $documentType
     * @return Orders
     * @throws EdifactException
     */
    public function setOrderNumber($orderNumber, $documentType = '120')
    {
        $this->isAllowed($documentType, [
            '120', '220', '221', '226', '227', '228', '126', 'YA8', 'YS8', 'YK8', '248', '447'
        ]);
        $this->orderNumber = ['BGM', $documentType, $orderNumber];
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * @param array $orderDate
     * @return Orders
     * @throws EdifactException
     */
    public function setOrderDate($orderDate)
    {
        $this->orderDate = $this->addDTMSegment($orderDate, '4');
        return $this;
    }

    /**
     * @return array
     */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
     * @param string|\DateTime $deliveryDate
     * @return $this
     * @throws EdifactException
     */
    public function setDeliveryDate($deliveryDate)
    {
        $this->deliveryDate = $this->addDTMSegment($deliveryDate, '2');
        return $this;
    }

    /**
     * @return array
     */
    public function getCollectiveOrderNumber()
    {
        return $this->collectiveOrderNumber;
    }

    /**
     * set a reference for qualifier ACD
     * @param string $collectiveOrderNumber
     * @return Orders
     */
    public function setCollectiveOrderNumber($collectiveOrderNumber)
    {
        $this->collectiveOrderNumber = $this->addRFFSegment('ACD', $collectiveOrderNumber);
        return $this;
    }

    /**
     * @return array
     */
    public function getInternalIdentifier()
    {
        return $this->internalIdentifier;
    }

    /**
     * set a reference for qualifier AAS
     * @param string $internalIdentifier
     * @return Orders
     */
    public function setInternalIdentifier($internalIdentifier)
    {
        $this->internalIdentifier = $this->addRFFSegment('AAS', $internalIdentifier);
        return $this;
    }

    /**
     * @return array
     */
    public function getObjectNumber()
    {
        return $this->objectNumber;
    }

    /**
     * set a reference for qualifier AEP
     * @param string $objectNumber
     * @return Orders
     */
    public function setObjectNumber($objectNumber)
    {
        $this->objectNumber = $this->addRFFSegment('AEP', $objectNumber);
        return $this;
    }

    /**
     * @return array
     */
    public function getObjectDescription1()
    {
        return $this->objectDescription1;
    }

    /**
     * set a reference for qualifier AFO
     * @param string $objectDescription1
     * @return Orders
     */
    public function setObjectDescription1($objectDescription1)
    {
        $this->objectDescription1 = $this->addRFFSegment('AFO', $objectDescription1);
        return $this;
    }

    /**
     * @return array
     */
    public function getObjectDescription2()
    {
        return $this->objectDescription2;
    }

    /**
     * set a reference for qualifier AFP
     * @param string $objectDescription2
     * @return Orders
     */
    public function setObjectDescription2($objectDescription2)
    {
        $this->objectDescription2 = $this->addRFFSegment('AFP', $objectDescription2);
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderDescription()
    {
        return $this->orderDescription;
    }

    /**
     * @param string $orderDescription
     * @return Orders
     */
    public function setOrderDescription($orderDescription)
    {
        $this->orderDescription = self::addFTXSegment($orderDescription, 'ORI');
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderNotification()
    {
        return $this->orderNotification;
    }

    /**
     * @param string $orderNotification
     * @return Orders
     */
    public function setOrderNotification($orderNotification)
    {
        $this->orderNotification = self::addFTXSegment($orderNotification, 'DIN');
        return $this;
    }

    /**
     * @return array
     */
    public function getDeliveryTerms()
    {
        return $this->deliveryTerms;
    }

    /**
     * @param string $deliveryTerms
     * @return Orders
     * @throws EdifactException
     */
    public function setDeliveryTerms($deliveryTerms)
    {
        $this->isAllowed(
            $deliveryTerms,
            ['CAF', 'DDP', 'DAF', 'FCA', 'CAI', 'ZZZ']
        );
        $this->deliveryTerms = ['TOD', '6', '', $deliveryTerms];
        return $this;
    }


}