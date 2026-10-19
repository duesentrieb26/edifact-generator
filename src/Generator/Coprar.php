<?php

namespace EDI\Generator;

/**
 * COPRAR message (container discharge/loading order), UN/EDIFACT D.95B with SMDG16 association.
 *
 * The carrier tells the terminal which containers to discharge from or load onto a vessel.
 *
 * @package EDI\Generator
 */
class Coprar extends Message {
    /** @var array|null TDT+20 segment, vessel and voyage */
    private $vessel;
    /** @var array|null LOC segment, port of loading or discharge */
    private $port;
    /** @var array|null NAD+CA segment, carrier */
    private $messageCA;
    /** @var array|null DTM+132 segment, estimated time of arrival */
    private $eta;
    /** @var array|null DTM+133 segment, estimated time of departure */
    private $etd;

    /** @var Coprar\Container[] containers to discharge or load */
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
    public function __construct($messageID = null, $identifier = 'COPRAR', $version = 'D', $release = '95B', $controllingAgency = 'UN', $association = 'SMDG16') {
        parent::__construct($identifier, $version, $release, $controllingAgency, $messageID, $association);

        $this->containers = [];
    }

    /**
     * Sets the carrier (NAD+CA segment).
     *
     * @param string $line carrier code from the SMDG Master Liner Codes List (code list 160, agency 20)
     *
     * @return $this
     */
    public function setCarrier($line) {
        $this->messageCA = ['NAD', 'CA', [$line, 160, 20]];
        return $this;
    }

    /**
     * Sets the vessel call (TDT+20 segment, mode 1 = maritime).
     *
     * @param string $extVoyage external voyage number
     * @param string $line      carrier code (code list 172, agency 20 = BIC)
     * @param string $vslName   vessel name
     * @param string $callsign  vessel call sign (code list 103)
     *
     * @return $this
     */
    public function setVessel($extVoyage, $line, $vslName, $callsign) {
        $this->vessel = self::tdtSegment(20, $extVoyage, 1, '', [$line, 172, 20], '', '', [$callsign, 103, '', $vslName]);
        return $this;
    }

    /**
     * Sets the port of loading or discharge, optionally with the terminal (LOC segment).
     *
     * @param int         $type     place qualifier (DE 3227): 9 = port of loading, 11 = port of discharge
     * @param string      $locode   UN/LOCODE of the port
     * @param string|null $terminal terminal code, sent as a second location with qualifiers TER and ZZZ
     *
     * @return $this
     */
    public function setPort($type, $locode, $terminal = null) {
        if ($terminal === null) {
            $this->port = self::locSegment($type, [$locode, 139, 6]);
        } else {
            $this->port = self::locSegment($type, [$locode, 139, 6], [$terminal, 'TER', 'ZZZ']);
        }
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
     * Adds a container to discharge or load.
     *
     * @param Coprar\Container $container container with its EQD group
     *
     * @return $this
     */
    public function addContainer(Coprar\Container $container) {
        $this->containers[] = $container;
        return $this;
    }

    /**
     * Builds the message segments (BGM, RFF, TDT, LOC, DTM, NAD, the container groups and CNT) and wraps them in UNH/UNT.
     *
     * @param int $msgStatus    message function (DE 1225): 9 = original, 5 = replacement
     * @param int $documentCode document name code (DE 1001): 43 = discharge, 45 = loading
     *
     * @return $this
     * @throws EdifactException when the message has no content
     */
    public function compose($msgStatus = 9, $documentCode = 45) {
        $this->messageContent = [
            ['BGM', $documentCode, $this->messageID, $msgStatus],
            self::rffSegment('XXX', 1)
        ];

        $this->messageContent[] = $this->vessel;
        $this->messageContent[] = $this->port;
        $this->messageContent[] = $this->eta;
        $this->messageContent[] = $this->etd;
        $this->messageContent[] = $this->messageCA;

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
