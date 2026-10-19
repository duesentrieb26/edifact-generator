<?php
namespace EDI\Generator\Vermas;

/**
 * One container of a VERMAS message: the EQD segment group with booking, seal, verified gross mass,
 * weighing date and method, weighing station, shipper and contact.
 *
 * @package EDI\Generator\Vermas
 */
class Container
{

    /** @var array|null EQD+CN segment, container number and size */
    private $cntr;
    /** @var array|null RFF+BN and optional RFF+SQ segments, booking */
    private $bkg;
    /** @var array|null SEL segment, seal */
    private $seal;
    /** @var array|null MEA+AAE segment, verified gross mass */
    private $measures;
    /** @var array|null DTM+798 segment, date of weighing */
    private $weighDate;
    /** @var array|null DOC segment, weighing method and certificate */
    private $weighMethod;
    /** @var array|null NAD+WPA segment, weighing station */
    private $spcWpa;
    /** @var array|null NAD+SPC segment, shipper responsible for the VGM */
    private $shipper;
    /** @var array|null CTA and optional COM segments, contact at the shipper */
    private $spcContact;

    /**
     * Creates an empty container.
     */
    public function __construct()
    {

    }

    /**
     * Sets the container (EQD+CN segment).
     *
     * @param string $number      container number
     * @param string $size        size and type code, e.g. 22G1, 42G1 (code list 6346 = ISO 6346, agency 306 = SMDG)
     * @param bool   $fixedFields true to also send full/empty indicator 5 (full)
     *
     * @return $this
     */
    public function setContainer($number, $size, $fixedFields = false)
    {
        $this->cntr = \EDI\Generator\Message::eqdSegment('CN', $number, [$size, '6346', '306']);
        if ($fixedFields) {
            $this->cntr = \EDI\Generator\Message::eqdSegment('CN', $number, [$size, '6346', '306'], '', '', 5);
        }
        return $this;
    }

    /**
     * Sets the booking number (RFF+BN segment) and optionally the sequence within the booking (RFF+SQ segment).
     *
     * @param string          $booking  booking number
     * @param string|int|null $sequence sequence number within the booking
     *
     * @return $this
     */
    public function setBooking($booking, $sequence = null)
    {
        $bkg = [];
        $bkg[]= \EDI\Generator\Message::rffSegment('BN', $booking);
        if ($sequence !== null) {
            $bkg[]= \EDI\Generator\Message::rffSegment('SQ', $sequence);
        }
        $this->bkg = $bkg;
        return $this;
    }

    /**
     * Sets the seal (SEL segment).
     *
     * @param string $seal       seal number, free text
     * @param string $sealIssuer seal issuer (DE 9303)
     *
     * @return $this
     */
    public function setSeal($seal, $sealIssuer)
    {
        $this->seal = ['SEL', [$seal, $sealIssuer]];
        return $this;
    }

    /**
     * Sets the verified gross mass (MEA+AAE segment).
     *
     * @param string           $weightMode measurement type (DE 6313), e.g. VGM = verified gross mass
     * @param string|int|float $weight     weight value
     * @param string           $unit       unit: KGM or LBS
     *
     * @return $this
     */
    public function setMeasures($weightMode, $weight, $unit = 'KGM')
    {
        $this->measures = ['MEA', 'AAE', $weightMode, [$unit, $weight]];
        return $this;
    }

    /**
     * Sets the SOLAS weighing method and its certificate (DOC segment).
     *
     * @param string $type weighing method: SM1 or SM2
     * @param string $cert documentation identification
     *
     * @return $this
     */
    public function setWeighMethod($type, $cert)
    {
        $this->weighMethod = ['DOC', [$type, 'VGM', 306], $cert];
        return $this;
    }

    /**
     * Sets the date of weighing (DTM+798 segment).
     *
     * @param string|null $date date and time as CCYYMMDDHHMM, now when null
     *
     * @return $this
     */
    public function setWeighDate($date = null)
    {
        if ($date === null) {
            $date = date('YmdHi');
        }
        $this->weighDate = \EDI\Generator\Message::dtmSegment(798, $date);
        return $this;
    }


    /**
     * Sets the weighing station (NAD+WPA segment).
     *
     * @param string $spcWpa weighing station identification
     *
     * @return $this
     */
    public function setWeighingStationId($spcWpa)
    {
        $this->spcWpa = ['NAD', 'WPA', $spcWpa];
        return $this;
    }

    /**
     * Sets the shipper responsible for the SOLAS verified gross mass (NAD+SPC segment).
     *
     * @param string      $spcShipper shipper name
     * @param string|null $spcCity    shipper city
     *
     * @return $this
     */
    public function setShipper($spcShipper, $spcCity = null)
    {
        $this->shipper = ['NAD', 'SPC', '', '', $spcShipper];
        if ($spcCity !== null) {
            $this->shipper[] = '';
            $this->shipper[] = $spcCity;
        }
        return $this;
    }

    /**
     * Sets the contact at the shipper (CTA segment) and optionally how to reach them (COM segment).
     *
     * @param string      $cntType  contact function (DE 3139), e.g. RP = responsible person
     * @param string      $cntTitle contact name, free text
     * @param string|null $comType  communication channel (DE 3155), COM is sent only when given
     * @param string|null $comData  address or number, free text
     *
     * @return $this
     */
    public function setSpcContact($cntType, $cntTitle, $comType = null, $comData = null)
    {
        $this->spcContact = [];
        $this->spcContact[] = ['CTA', $cntType, ['', $cntTitle]];
        if ($comType !== null) {
            $this->spcContact[] = ['COM', [$comData, $comType]];
        }
        return $this;
    }

    /**
     * Returns the container segments in message order (EQD, RFF, SEL, MEA, DTM, DOC, NAD, CTA, COM).
     *
     * @return array list of segments
     */
    public function compose()
    {
        $composed = [];
        if ($this->cntr !== null) {
            $composed[] = $this->cntr;
        }
        if ($this->bkg !== null) {
            $composed[] = $this->bkg[0];
            if (isset($this->bkg[1])) {
                $composed[] = $this->bkg[1];
            }
        }
        if ($this->seal !== null) {
            $composed[] = $this->seal;
        }
        if ($this->measures !== null) {
            $composed[] = $this->measures;
        }
        if ($this->weighDate !== null) {
            $composed[] = $this->weighDate;
        }
        if ($this->weighMethod !== null) {
            $composed[] = $this->weighMethod;
        }
        if ($this->spcWpa !== null) {
            $composed[] = $this->spcWpa;
        }
        if ($this->shipper !== null) {
            $composed[] = $this->shipper;
        }
        if ($this->spcContact !== null) {
            $composed[] = $this->spcContact[0];
            if (isset($this->spcContact[1])) {
                $composed[] = $this->spcContact[1];
            }

        }
        return $composed;
    }
}
