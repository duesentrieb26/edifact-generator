<?php

namespace EDI\Generator;

/**
 * CALINF message (vessel call information), UN/EDIFACT D.00B with SMDG20 association.
 *
 * The vessel agent tells the terminal which vessel calls, with its voyage, call sign and ETA/ETD.
 *
 * @package EDI\Generator
 */
class Calinf extends Message {
    /** @var array DTM+137 segment, date and time the message was sent */
    private $dtmSend;
    /** @var array|null NAD+MS segment, message sender */
    private $messageSender;
    /** @var array|null NAD+MR segment, message receiver */
    private $messageReceiver;
    /** @var array|null TDT+20 segment, vessel and voyage */
    private $vessel;
    /** @var array|null DTM+132 segment, estimated time of arrival */
    private $eta;
    /** @var array|null DTM+133 segment, estimated time of departure */
    private $etd;
    /** @var array|null RFF+VM segment, vessel call sign */
    private $callsign;

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
    public function __construct($messageID = null, $identifier = 'CALINF', $version = 'D', $release = '00B', $controllingAgency = 'UN', $association = 'SMDG20') {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->dtmSend = self::dtmSegment(137, date('YmdHi'));
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
     * Sets the message sender, usually the vessel agent (NAD+MS segment).
     *
     * @param string $code party identification
     * @param string $name party name
     *
     * @return $this
     */
    public function setMessageSender($code, $name) {
        $this->messageSender = ['NAD', 'MS', $code, $name];
        return $this;
    }

    /**
     * Sets the message receiver, usually the terminal (NAD+MR segment).
     *
     * @param string $code party identification
     * @param string $name party name
     *
     * @return $this
     */
    public function setMessageReceiver($code, $name) {
        $this->messageReceiver = ['NAD', 'MR', $code, $name];
        return $this;
    }

    /**
     * Sets the vessel call (TDT+20 segment) and the vessel call sign (RFF+VM segment).
     *
     * @param string $extVoyage external voyage number
     * @param string $line      carrier code (code list 172, agency 20 = BIC)
     * @param string $imoNumber IMO number of the vessel (code list 146, agency 54 = IMO)
     * @param string $vslName   vessel name
     * @param string $callsign  vessel call sign
     *
     * @return $this
     */
    public function setVessel($extVoyage, $line, $imoNumber, $vslName, $callsign) {
        $this->vessel = ['TDT', 20, $extVoyage, '', '', [$line, 172, 20], '', '', [$imoNumber, 146, 54, $vslName]];
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
    public function setEta($dtm) {
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
    public function setEtd($dtm) {
        $this->etd = self::dtmSegment(133, $dtm);
        return $this;
    }

    /**
     * Builds the message segments (BGM, DTM, NAD, TDT, DTM, RFF) and wraps them in UNH/UNT.
     *
     * @param int $msgStatus    message function (DE 1225), e.g. 5 = replace, 9 = original
     * @param int $documentCode document name code (DE 1001)
     *
     * @return $this
     * @throws EdifactException when the message has no content
     */
    public function compose($msgStatus = 5, $documentCode = 96) {
        $this->messageContent = [
            ['BGM', $documentCode, $this->messageID, $msgStatus]
        ];

        $this->messageContent[] = $this->dtmSend;
        $this->messageContent[] = $this->messageSender;
        $this->messageContent[] = $this->messageReceiver;
        $this->messageContent[] = $this->vessel;
        $this->messageContent[] = $this->eta;
        $this->messageContent[] = $this->etd;
        $this->messageContent[] = $this->callsign;

        parent::compose();
        return $this;
    }
}
