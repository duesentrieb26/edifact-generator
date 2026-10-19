<?php

namespace EDI\Generator;

/**
 * COPINO message (container pre-notification), UN/EDIFACT D.95B with ITG13 association.
 *
 * A haulier announces to the terminal that a truck will deliver or pick up a container.
 *
 * @package EDI\Generator
 */
class Copino extends Message {
    /** @var array|null TDT+1 segment, truck, carrier and driver */
    private $transporter;
    /** @var array|null TDT+20 segment, vessel */
    private $vessel;
    /** @var array|null LOC+88 segment, place of receipt with terminal */
    private $port;
    /** @var array|null LOC+7 segment, place of delivery */
    private $destination;
    /** @var array|null DTM+132 segment, estimated date and time */
    private $dtm;
    /** @var array container segments (SGP, EQD, RFF+BN, RFF+SQ) */
    private $cntr;
    /** @var array|null MEA+AAE segment, container weight */
    private $measures;

    /**
     * Creates the message without container and measures.
     *
     * @param string|null $messageID         message reference (UNH), generated when null
     * @param string      $identifier        message type
     * @param string      $version           message version
     * @param string      $release           message release
     * @param string      $controllingAgency controlling agency
     * @param string|null $association       association assigned code
     */
    public function __construct($messageID = null, $identifier = 'COPINO', $version = 'D', $release = '95B', $controllingAgency = 'UN', $association = 'ITG13') {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->cntr = [];
        $this->measures = [];
    }

    /**
     * Sets the message sender (NAD+MS segment) and receiver (NAD+MR segment).
     *
     * @param string $sender   sender identification
     * @param string $receiver receiver identification
     *
     * @return $this
     */
    public function setSenderAndReceiver($sender, $receiver) {
        $this->sender = ['NAD', 'MS', $sender];
        $this->receiver = ['NAD', 'MR', $receiver];
        return $this;
    }

    /**
     * Sets the trucker (TDT+1 segment).
     *
     * @param string $transRef         conveyance reference number
     * @param int    $modeOfTransport  mode of transport (DE 8067), e.g. 3 = road
     * @param int    $meansOfTransport type of means of transport (DE 8179), e.g. 31 = truck
     * @param string $carrierName      carrier
     * @param string $plate            licence plate of the truck
     * @param string $driver           driver name
     *
     * @return $this
     */
    public function setTransporter($transRef, $modeOfTransport, $meansOfTransport, $carrierName, $plate, $driver) {
        $this->transporter = self::tdtSegment(1, $transRef, $modeOfTransport, $meansOfTransport, $carrierName, '', '', [$plate, 146, '', $driver]);
        return $this;
    }

    /**
     * Sets the ocean vessel (TDT+20 segment, mode 1 = maritime, means 13 = ocean vessel).
     *
     * @param string $carrierName carrier
     * @param string $callsign    vessel call sign (code list 103)
     * @param string $vesselName  vessel name
     *
     * @return $this
     */

    public function setVessel($carrierName, $callsign, $vesselName) {
        $this->vessel = self::tdtSegment(20, '', 1, 13, $carrierName, '', '', [$callsign, 103, '', $vesselName]);
        return $this;
    }

    /**
     * Sets the estimated date and time of the truck visit (DTM+132 segment).
     *
     * @param string $dtm date and time as CCYYMMDDHHMM
     *
     * @return $this
     */
    public function setDTM($dtm) {
        $this->dtm = self::dtmSegment(132, $dtm);
        return $this;
    }

    /**
     * Sets the container (SGP, EQD+CN with status 2 = export and 5 = full, RFF+BN and RFF+SQ segments).
     *
     * @param string     $number   container number
     * @param string     $size     size and type code, e.g. 22G1, 42G1
     * @param string     $booking  booking number
     * @param string|int $sequence sequence number within the booking
     *
     * @return $this
     */
    public function setContainer($number, $size, $booking, $sequence) {
        $cntr = [];
        $cntr[] = ['SGP', $number];
        $cntr[] = self::eqdSegment('CN', $number, [$size, '102', '5'], '', 2, 5);
        $cntr[] = self::rffSegment('BN', $booking);
        $cntr[] = self::rffSegment('SQ', $sequence);
        $this->cntr = $cntr;
        return $this;
    }

    /**
     * Sets the container weight (MEA+AAE segment).
     *
     * @param string           $weightMode measurement type (DE 6313), e.g. G = gross mass, VGM = verified gross mass
     * @param string|int|float $weight     weight value
     * @param string           $unit       unit: KGM or LBS
     *
     * @return $this
     */
    public function setMeasures($weightMode, $weight, $unit = 'KGM') {
        $this->measures = ['MEA', 'AAE', $weightMode, [$unit, $weight]];
        return $this;
    }

    /**
     * Sets the place of receipt with its terminal (LOC+88 segment).
     *
     * @param string $locode   UN/LOCODE of the port
     * @param string $terminal terminal code (code list 72, agency 306 = SMDG)
     *
     * @return $this
     */
    public function setPort($locode, $terminal) {
        $this->port = self::locSegment(88, [$locode, 139, 6], [$terminal, 72, 306]);
        return $this;
    }

    /**
     * Sets the place of delivery (LOC+7 segment).
     *
     * @param string $locode UN/LOCODE of the place
     *
     * @return $this
     */
    public function setDestination($locode) {
        $this->destination = self::locSegment(7, [$locode, 139, 6]);
        return $this;
    }

    /**
     * Builds the message segments (BGM, RFF, TDT, LOC, DTM, NAD, GID, container, MEA, CNT) and wraps them in UNH/UNT.
     *
     * @param int $msgStatus    message function (DE 1225), e.g. 9 = original
     * @param int $documentCode document name code (DE 1001): 660 = delivery, 661 = pickup
     *
     * @return $this
     * @throws EdifactException when the message has no content
     */
    public function compose($msgStatus = 9, $documentCode = 661) {
        $this->messageContent = [
            ['BGM', $documentCode, $this->messageID, $msgStatus],
            self::rffSegment('XXX', 1)
        ];

        if ($this->transporter !== null) {
            $this->messageContent[] = $this->transporter;
        }
        $this->messageContent[] = $this->port;
        $this->messageContent[] = $this->dtm;
        $this->messageContent[] = $this->sender;
        $this->messageContent[] = $this->receiver;
        $this->messageContent[] = ['GID', 1];
        foreach ($this->cntr as $segment) {
            $this->messageContent[] = $segment;
        }
        $this->messageContent[] = $this->measures;
        $this->messageContent[] = $this->vessel;
        $this->messageContent[] = $this->destination;
        $this->messageContent[] = ['CNT', [16, 1]];

        parent::compose();
        return $this;
    }
}
