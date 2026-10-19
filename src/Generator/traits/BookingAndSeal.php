<?php

namespace EDI\Generator\Traits;

use EDI\Generator\Message;

/**
 * Trait BookingAndSeal
 * Booking, bill of lading and seal of a container, shared by the CODECO and COPRAR containers
 * @package EDI\Generator\Traits
 */
trait BookingAndSeal {
    private $bkg;
    private $seal;

    public function setBooking($booking) {
        $this->bkg = Message::rffSegment('BN', $booking);
        return $this;
    }

    public function setBillOfLading($bl) {
        $this->bkg = Message::rffSegment('BM', $bl);
        return $this;
    }

    /*
     * $seal = free text
     * $sealIssuer = DE 9303
     */
    public function setSeal($seal, $sealIssuer) {
        $this->seal = ['SEL', [$seal, $sealIssuer]];
        return $this;
    }
}
