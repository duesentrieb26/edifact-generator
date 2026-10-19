<?php

namespace EDI\Generator;

/**
 * CODECO message (container gate-in/gate-out report), UN/EDIFACT D.95B.
 *
 * A terminal or depot reports containers that entered or left the facility.
 *
 * @package EDI\Generator
 */
class Codeco extends Message {
    /** @var array|null NAD+CF segment, container operator (carrier) */
    private $messageCF;

    /** @var Codeco\Container[] containers reported in this message */
    private $containers;

    /**
     * Creates the message with an empty container list.
     *
     * @param string|null $messageID         message reference (UNH), generated when null
     * @param string      $identifier        message type
     * @param string      $version           message version
     * @param string      $release           message release
     * @param string      $controllingAgency controlling agency
     * @param string|null $association       association assigned code
     */
    public function __construct($messageID = null, $identifier = 'CODECO', $version = 'D', $release = '95B', $controllingAgency = 'UN', $association = null) {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->containers = [];
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
     * Sets the carrier (NAD+CF segment).
     *
     * @param string $line carrier code from the SMDG Master Liner Codes List (code list 160, agency 166)
     *
     * @return $this
     */
    public function setCarrier($line) {
        $this->messageCF = ['NAD', 'CF', [$line, 160, 166]];
        return $this;
    }

    /**
     * Adds a container to the report.
     *
     * @param Codeco\Container $container container with its EQD group
     *
     * @return $this
     */
    public function addContainer(Codeco\Container $container) {
        $this->containers[] = $container;
        return $this;
    }

    /**
     * Builds the message segments (BGM, NAD, the container groups and CNT) and wraps them in UNH/UNT.
     *
     * @param int $msgStatus    message function (DE 1225), e.g. 5 = replace, 9 = original
     * @param int $documentCode document name code (DE 1001): 34 = gate in, 36 = gate out
     *
     * @return $this
     * @throws EdifactException when the message has no content
     */
    public function compose($msgStatus = 5, $documentCode = 34) {
        $this->messageContent = [
            ['BGM', $documentCode, $this->messageID, $msgStatus]
        ];

        if ($this->sender !== null) {
            $this->messageContent[] = $this->sender;
        }
        if ($this->receiver !== null) {
            $this->messageContent[] = $this->receiver;
        }
        if ($this->messageCF !== null) {
            $this->messageContent[] = $this->messageCF;
        }

        foreach ($this->containers as $cntr) {
            $content = $cntr->compose();
            foreach ($content as $segment) {
                $this->messageContent[] = $segment;
            }
        }

        $this->messageContent[] = ['CNT', [16, count($this->containers)]];
        parent::compose();
        return $this;
    }
}
