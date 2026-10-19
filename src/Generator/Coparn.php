<?php

namespace EDI\Generator;

use EDI\Generator\Traits\Cargo;

/**
 * COPARN message (container announcement / release order), UN/EDIFACT D.00B with SMDG20 association.
 *
 * The carrier tells the terminal or depot which container (or how many) to release for a booking.
 *
 * @package EDI\Generator
 */
class Coparn extends Message {
    use Cargo;

    /** @var array DTM+137 segment, date and time the message was sent */
    private $dtmSend;
    /** @var array|null NAD+MS segment, message sender (the carrier) */
    private $messageSender;
    /** @var array|null message receiver, not sent */
    private $messageReceiver;
    /** @var array|null TDT+20 segment, vessel and voyage */
    private $vessel;
    /** @var array|null DTM+132 segment, estimated time of arrival */
    private $eta;
    /** @var array|null DTM+133 segment, estimated time of departure */
    private $etd;
    /** @var array|null RFF+VM segment, vessel call sign */
    private $callsign;
    /** @var array|null RFF+BN segment, booking number */
    private $booking;
    /** @var array|null RFF+SQ segment, sequence number within the booking */
    private $bookingSequence;
    /** @var array|null RFF+ATX segment, acceptance order reference */
    private $rffAcceptOrder;
    /** @var array|null LOC+9 segment, port of loading */
    private $pol;
    /** @var array|null LOC+11 segment, port of discharge */
    private $pod;
    /** @var array|null LOC+7 segment, final destination */
    private $fnd;
    /** @var array|null NAD+CF segment, container operator (carrier) */
    private $messageCF;
    /** @var array|null EQD+CN segment, a single container */
    private $cntr;
    /** @var array|null EQN segment, number of containers, sent only without a single container */
    private $cntrAmount;

    /**
     * Creates the message and sets the sending time to now.
     *
     * @param string|null $messageID         message reference (UNH), generated when null
     * @param string      $identifier        message type
     * @param string      $version           message version
     * @param string      $release           message release
     * @param string      $controllingAgency controlling agency
     * @param string|null $association       association assigned code
     */
    public function __construct($messageID = null, $identifier = 'COPARN', $version = 'D', $release = '00B', $controllingAgency = 'UN', $association = 'SMDG20') {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->dtmSend = self::dtmSegment(137, date('YmdHi'));
    }

    /**
     * Sets the carrier as message sender (NAD+MS segment) and container operator (NAD+CF segment).
     *
     * @param string $line carrier code from the SMDG Master Liner Codes List (code list 160)
     *
     * @return $this
     */
    public function setCarrier($line) {
        $this->messageSender = ['NAD', 'MS', [$line, 160, 'ZZZ']];
        $this->messageCF = ['NAD', 'CF', [$line, 160, 166]];
        return $this;
    }

    /**
     * Sets the date and time the message was sent (DTM+137 segment).
     *
     * @param string $dtm date and time as CCYYMMDDHHMM
     *
     * @return $this
     */
    public function setDTMMessageSendingTime($dtm) {
        $this->dtmSend = self::dtmSegment(137, $dtm);
        return $this;
    }

    /**
     * Sets the booking number (RFF+BN segment) and the sequence within the booking (RFF+SQ segment).
     *
     * @param string     $booking  booking number
     * @param string|int $sequence sequence number within the booking
     *
     * @return $this
     */
    public function setBooking($booking, $sequence) {
        $this->booking = self::rffSegment('BN', $booking);
        $this->bookingSequence = self::rffSegment('SQ', $sequence);
        return $this;
    }

    /**
     * Sets the acceptance order reference (RFF+ATX segment).
     *
     * @param string $atx order reference
     *
     * @return $this
     */
    public function setRFFOrder($atx) {
        $this->rffAcceptOrder = self::rffSegment('ATX', $atx);
        return $this;
    }

    /**
     * Sets the vessel call (TDT+20 segment) and the vessel call sign (RFF+VM segment).
     *
     * @param string $extVoyage external voyage number
     * @param string $line      carrier code (code list 172, agency 20 = BIC)
     * @param string $vslName   vessel name
     * @param string $callsign  vessel call sign (code list 146, agency 11 = Lloyd's)
     *
     * @return $this
     */
    public function setVessel($extVoyage, $line, $vslName, $callsign) {
        $this->vessel = self::tdtSegment(20, $extVoyage, '', '', [$line, 172, 20], '', '', [$callsign, 146, 11, $vslName]);
        $this->callsign = self::rffSegment('VM', $callsign);
        return $this;
    }

    /**
     * Sets the estimated time of arrival (DTM+132 segment).
     *
     * @param string $dtm date and time as CCYYMMDDHHMM
     *
     * @return $this
     */
    public function setETA($dtm) {
        $this->eta = self::dtmSegment(132, $dtm);
        return $this;
    }

    /**
     * Sets the estimated time of departure (DTM+133 segment).
     *
     * @param string $dtm date and time as CCYYMMDDHHMM
     *
     * @return $this
     */
    public function setETD($dtm) {
        $this->etd = self::dtmSegment(133, $dtm);
        return $this;
    }

    /**
     * Sets the port of loading (LOC+9 segment).
     *
     * @param string $loc UN/LOCODE of the port
     *
     * @return $this
     */
    public function setPOL($loc) {
        $this->pol = self::locSegment(9, [$loc, 139, 6]);
        return $this;
    }

    /**
     * Sets the port of discharge (LOC+11 segment).
     *
     * @param string $loc UN/LOCODE of the port
     *
     * @return $this
     */
    public function setPOD($loc) {
        $this->pod = self::locSegment(11, [$loc, 139, 6]);
        return $this;
    }

    /**
     * Sets the final destination (LOC+7 segment).
     *
     * @param string $loc UN/LOCODE of the place
     *
     * @return $this
     */
    public function setFND($loc) {
        $this->fnd = self::locSegment(7, [$loc, 139, 6]);
        return $this;
    }

    /**
     * Sets a single container to release (EQD+CN segment, status 2 = export, 5 = full).
     *
     * @param string $number container number
     * @param string $size   size and type code, e.g. 22G1, 42G1
     *
     * @return $this
     */
    public function setContainer($number, $size) {
        $this->cntr = self::eqdSegment('CN', $number, [$size, '102', '5'], '', 2, 5);
        return $this;
    }

    /**
     * Sets how many containers need to be released (EQN segment), used when no single container is set.
     *
     * @param int $total number of containers
     *
     * @return $this
     */
    public function setEquipmentQuantity($total) {
        $this->cntrAmount = ['EQN', $total];
        return $this;
    }

    /**
     * Builds the message segments (BGM, DTM, RFF, TDT, LOC, NAD, EQD/EQN, TMD, MEA, DIM, TMP, FTX, DGS, TDT, CNT)
     * and wraps them in UNH/UNT.
     *
     * @param int $msgStatus    message function (DE 1225), e.g. 5 = replace, 9 = original
     * @param int $documentCode document name code (DE 1001), 126 = release order
     *
     * @return $this
     * @throws EdifactException when the message has no content
     */
    public function compose($msgStatus = 5, $documentCode = 126) {
        $this->messageContent = [
            ['BGM', $documentCode, $this->messageID, $msgStatus, 'AB']
        ];

        $this->messageContent[] = $this->dtmSend;
        if ($this->rffAcceptOrder !== null) {
            $this->messageContent[] = $this->rffAcceptOrder;
        }
        $this->messageContent[] = $this->booking;
        $this->messageContent[] = $this->vessel;
        $this->messageContent[] = $this->callsign;
        $this->messageContent[] = $this->pol;
        $this->messageContent[] = $this->eta;
        $this->messageContent[] = $this->etd;
        $this->messageContent[] = $this->messageSender;
        $this->messageContent[] = $this->messageCF;
        $this->messageContent[] = $this->cntr;
        $this->messageContent[] = $this->bookingSequence;
        if ($this->cntr === null) {
            $this->messageContent[] = $this->cntrAmount;
        }
        $this->messageContent[] = ['TMD', '3'];
        if ($this->weightTime !== null) {
            $this->messageContent[] = $this->weightTime;
        }
        $this->messageContent[] = $this->fnd;
        $this->messageContent[] = $this->pol;
        $this->messageContent[] = $this->pod;
        $this->messageContent[] = $this->weight;
        if ($this->dimensions !== null) {
            foreach ($this->dimensions as $segment) {
                $this->messageContent[] = $segment;
            }
        }
        if ($this->temperature !== null) {
            $this->messageContent[] = $this->temperature;
        }
        $this->messageContent[] = $this->cargo;
        if ($this->dangerous !== null) {
            foreach ($this->dangerous as $segment) {
                $this->messageContent[] = $segment;
            }
        }
        $this->messageContent[] = ['TDT', 1, '', 3];
        $this->messageContent[] = ['CNT', [16, 1]];
        parent::compose();
        return $this;
    }
}
