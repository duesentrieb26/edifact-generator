<?php

namespace EDI\Generator\Traits;

/**
 * Delivery terms (TOD segment). Used by Desadv, Invoic and Ordrsp.
 *
 * @package EDI\Generator\Traits
 */
trait DeliveryTerms {
  /** @var array|null TOD segment, function code 6 (delivery condition) */
  protected $deliveryTerms;

  /**
   * Sets the delivery terms (TOD segment, function code 6).
   *
   * @param string $deliveryTerms delivery terms code, e.g. an Incoterm such as DDP or FCA
   *
   * @return $this
   */
  public function setDeliveryTerms($deliveryTerms) {
    $this->deliveryTerms = ['TOD', '6', '', $deliveryTerms];
    return $this;
  }
}
