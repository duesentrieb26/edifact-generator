<?php

namespace EDI\Generator\Traits;

use EDI\Generator\Message;

/**
 * Booking or bill of lading reference and seal of a container.
 * Used by Codeco\Container and Coprar\Container.
 *
 * @package EDI\Generator\Traits
 */
trait BookingAndSeal {
    /** @var array|null RFF segment with the booking (BN) or bill of lading (BM) number */
    private $bkg;
    /** @var array|null SEL segment with seal number and issuer */
    private $seal;

    /**
     * Sets the booking number (RFF segment, qualifier BN). Replaces a bill of lading set before.
     *
     * @param string $booking booking reference number
     *
     * @return $this
     */
    public function setBooking($booking) {
        $this->bkg = Message::rffSegment('BN', $booking);
        return $this;
    }

    /**
     * Sets the bill of lading number (RFF segment, qualifier BM). Replaces a booking set before.
     *
     * @param string $bl bill of lading number
     *
     * @return $this
     */
    public function setBillOfLading($bl) {
        $this->bkg = Message::rffSegment('BM', $bl);
        return $this;
    }

    /**
     * Sets the seal of the container (SEL segment).
     *
     * @param string $seal       seal number (free text)
     * @param string $sealIssuer seal issuer code (DE 9303, e.g. CA = carrier, SH = shipper, CU = customs)
     *
     * @return $this
     */
    public function setSeal($seal, $sealIssuer) {
        $this->seal = ['SEL', [$seal, $sealIssuer]];
        return $this;
    }
}
