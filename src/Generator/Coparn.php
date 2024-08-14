<?php

namespace EDI\Generator;

class Coparn extends Message {
    private $dtmSend;
    private $messageSender;
    private $messageReceiver;
    private $vessel;
    private $eta;
    private $etd;
    private $callsign;
    private $booking;
    private $bookingSequence;
    private $rffAcceptOrder;
    private $pol;
    private $pod;
    private $fnd;
    private $messageCF;
    private $cntr;
    private $cntrAmount;
    private $weight;
    private $weightTime;
    private $dangerous;
    private $temperature;
    private $dimensions;

    public function __construct($messageID = null, $identifier = 'COPARN', $version = 'D', $release = '00B', $controllingAgency = 'UN', $association = 'SMDG20') {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->dtmSend = self::dtmSegment(137, date('YmdHi'));

        $this->containers = [];
    }

    /*
     * $line: Master Liner Codes List
     */
    public function setCarrier($line) {
        $this->messageSender = ['NAD', 'MS', [$line, 160, 'ZZZ']];
        $this->messageCF = ['NAD', 'CF', [$line, 160, 166]];
        return $this;
    }

    /*
     * Date of the message submission
     *
     */
    public function setDTMMessageSendingTime($dtm) {
        $this->dtmSend = self::dtmSegment(137, $dtm);
        return $this;
    }

    /*
     * Date of the message submission
     *
     */
    public function setBooking($booking, $sequence) {
        $this->booking = self::rffSegment('BN', $booking);
        $this->bookingSequence = self::rffSegment('SQ', $sequence);
        return $this;
    }

    /*
     * Date of the message submission
     *
     */
    public function setRFFOrder($atx) {
        $this->rffAcceptOrder = self::rffSegment('ATX', $atx);
        return $this;
    }

    /*
     * Vessel call information
     *
     */
    public function setVessel($extVoyage, $line, $vslName, $callsign) {
        $this->vessel = self::tdtSegment(20, $extVoyage, '', '', [$line, 172, 20], '', '', [$callsign, 146, 11, $vslName]);
        $this->callsign = self::rffSegment('VM', $callsign);
        return $this;
    }

    /*
     * Estimated Time of Arrival
     *
     */
    public function setETA($dtm) {
        $this->eta = self::dtmSegment(132, $dtm);
        return $this;
    }

    /*
     * Estimated Time of Departure
     *
     */
    public function setETD($dtm) {
        $this->etd = self::dtmSegment(133, $dtm);
        return $this;
    }

    /*
     * Port of Loading
     *
     */
    public function setPOL($loc) {
        $this->pol = self::locSegment(9, [$loc, 139, 6]);
        return $this;
    }

    /*
     * Port of Discharge
     *
     */
    public function setPOD($loc) {
        $this->pod = self::locSegment(11, [$loc, 139, 6]);
        return $this;
    }

    /*
     * Final destination
     *
     */
    public function setFND($loc) {
        $this->fnd = self::locSegment(7, [$loc, 139, 6]);
        return $this;
    }

    /*
     * $size = 22G1, 42G1, etc
     */
    public function setContainer($number, $size) {
        $this->cntr = self::eqdSegment('CN', $number, [$size, '102', '5'], '', 2, 5);
        return $this;
    }

    /*
     * How many containers need to be released
     *
     */
    public function setEquipmentQuantity($total) {
        $this->cntrAmount = ['EQN', $total];
        return $this;
    }

    /*
     * VGM information
     *
     */
    public function setVGM($weight, $weightTime) {
        $this->weight = ['MEA', 'AAE', 'VGM', ['KGM', $weight]];
        $this->weightTime = self::dtmSegment(798, $weightTime);
        return $this;
    }

    /*
     * Weight information
     *
     */
    public function setGrossWeight($weight) {
        $this->weight = ['MEA', 'AAE', 'G', ['KGM', $weight]];
        return $this;
    }

    /*
     * Cargo category
     *
     */
    public function setCargoCategory($text) {
        $this->cargo = ['FTX', 'AAA', '', '', $text];
        return $this;
    }

    /*
     * DEPRECATED
     */
    public function setDangerous($hazardClass, $hazardCode) {
        $this->addDangerous($hazardClass, $hazardCode);
        return $this;
    }

    public function addDangerous($hazardClass, $hazardCode, $flashpoint = null, $packingGroup = null) {
        if ($this->dangerous === null) {
            $this->dangerous = [];
        }

        $dgs = ['DGS', 'IMD', $hazardClass, $hazardCode];
        if ($flashpoint !== null) {
            $dgs[] = [$flashpoint, 'CEL'];
            if ($packingGroup !== null) {
                $dgs[] = [$packingGroup, 'CEL'];
            }
        }

        $this->dangerous[] = $dgs;
        return $this;
    }

    public function setTemperature($setDegrees) {
        $this->temperature = ['TMP', '2', [$setDegrees, 'CEL']];
        return $this;
    }

    public function setOverDimensions($front = '', $back = '', $right = '', $left = '', $height = '') {
        $this->dim = [];
        if ($front !== '') {
            $this->dimensions[] = ['DIM', '5', ['CMT', $front]];
        }
        if ($back !== '') {
            $this->dimensions[] = ['DIM', '6', ['CMT', $back]];
        }
        if ($right !== '') {
            $this->dimensions[] = ['DIM', '7', ['CMT', '', $right]];
        }
        if ($left !== '') {
            $this->dimensions[] = ['DIM', '8', ['CMT', '', $left]];
        }
        if ($height !== '') {
            $this->dimensions[] = ['DIM', '13', ['CMT', '', '', $height]];
        }
        return $this;
    }

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
        if ($this->cntr  === '') {
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
