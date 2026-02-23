<?php

namespace EDI\Generator\Traits;

trait DeliveryTerms {
  protected $deliveryTerms;

  public function setDeliveryTerms($deliveryTerms) {
    $this->deliveryTerms = ['TOD', '6', '', $deliveryTerms];
    return $this;
  }
}
