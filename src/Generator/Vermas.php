<?php

namespace EDI\Generator;

/**
 * VERMAS message (verified gross mass), UN/EDIFACT D.16A with SMDG10 association.
 *
 * Reports the SOLAS verified gross mass (VGM) of one or more containers to the carrier or terminal.
 *
 * @package EDI\Generator
 */
class Vermas extends Message {
    /** @var array DTM+137 segment, date and time the message was sent */
    private $dtmSend;

    /** @var array|string NAD+CA segment, carrier; '' when not set */
    private $messageLine = '';
    /** @var array|string CTA segment, contact of the sender; '' when not set */
    private $messageSender = '';
    /** @var array|string COM segment, communication contact of the sender; '' when not set */
    private $messageSenderInformation = '';
    /** @var array NAD+TB segment, company of the sender */
    private $messageSenderCompany = ['NAD', 'TB'];

    /** @var Vermas\Container[] containers with their VGM information */
    private $containers;

    /**
     * Creates the message with an empty container list and sets the sending time to now.
     *
     * @param string|null $messageID         message reference (UNH), generated when null
     * @param string      $identifier        message type
     * @param string      $version           message version
     * @param string      $release           message release
     * @param string      $controllingAgency controlling agency
     * @param string|null $association       association assigned code
     */
    public function __construct($messageID = null, $identifier = 'VERMAS', $version = 'D', $release = '16A', $controllingAgency = 'UN', $association = 'SMDG10') {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->dtmSend = self::dtmSegment(137, date('YmdHi'));

        $this->containers = [];
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
     * Sets the carrier (NAD+CA segment).
     *
     * @param string $line carrier code from the SMDG Master Liner Codes List (code list LINES, agency 306 = SMDG)
     *
     * @return $this
     */
    public function setCarrier($line) {
        $this->messageLine = ['NAD', 'CA', [$line, 'LINES', 306]];
        return $this;
    }

    /**
     * Sets the company that sends the message (NAD+TB segment).
     *
     * @param string $companyName company name or identification
     *
     * @return $this
     */
    public function setMessageSenderCompany($companyName) {
        $this->messageSenderCompany = ['NAD', 'TB',  $companyName];
        return $this;
    }

    /**
     * Sets the contact person of the sender (CTA segment).
     *
     * @param string $cntFunctionCode contact function (DE 3139)
     * @param string $cntIdentifier   contact identification, free text
     * @param string $cntName         contact name, free text
     *
     * @return $this
     */
    public function setMessageSender($cntFunctionCode, $cntIdentifier, $cntName) {
        $this->messageSender = ['CTA', $cntFunctionCode, [$cntIdentifier, $cntName]];
        return $this;
    }

    /**
     * Sets how to reach the sender (COM segment).
     *
     * @param string $comType communication channel (DE 3155), e.g. EM = e-mail, TE = telephone
     * @param string $comData address or number, free text
     *
     * @return $this
     */
    public function setMessageSenderInformation($comType, $comData) {
        $this->messageSenderInformation = ['COM', [$comData, $comType]];
        return $this;
    }

    /**
     * Adds a container with its VGM information.
     *
     * @param Vermas\Container $container container with its EQD group
     *
     * @return $this
     */
    public function addContainer(Vermas\Container $container) {
        $this->containers[] = $container;
        return $this;
    }

    /**
     * Builds the message segments (BGM, DTM, NAD, CTA, COM and the container groups) and wraps them in UNH/UNT.
     *
     * @param int $msgStatus    message function (DE 1225), e.g. 5 = replace, 9 = original
     * @param int $documentCode document name code (DE 1001), 749 = verified gross mass
     *
     * @return $this
     * @throws EdifactException when the message has no content
     */
    public function compose($msgStatus = 5, $documentCode = 749) {
        $this->messageContent = [
            ['BGM', $documentCode, $this->messageID, $msgStatus]
        ];

        /* message creation date and time */
        $this->messageContent[] = $this->dtmSend;

        $this->messageContent[] = $this->messageSenderCompany;

        /* carrier line */
        if ($this->messageLine !== '') {
            $this->messageContent[] = $this->messageLine;
        }

        /* sender information */
        if ($this->messageSender !== '') {
            $this->messageContent[] = $this->messageSender;
        }
        if ($this->messageSenderInformation !== '') {
            $this->messageContent[] = $this->messageSenderInformation;
        }

        /* equipment and vgm information */
        foreach ($this->containers as $cntr) {
            $content = $cntr->compose();
            foreach ($content as $segment) {
                $this->messageContent[] = $segment;
            }
        }

        parent::compose();
        return $this;
    }
}
