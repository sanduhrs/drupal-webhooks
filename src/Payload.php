<?php

namespace Drupal\webhooks;

class Payload {
  /**
   * @var array
   */
  protected $payload;

  /**
   * Payload constructor.
   *
   * @param array $payload
   */
  public function __construct(array $payload) {
    $this->payload = $payload;
  }

  /**
   * @return array
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * @param array $payload
   */
  public function setPayload($payload) {
    $this->payload = $payload;
  }

  public function addToPayload($payload_element) {
    array_push($this->payload, $payload_element);
  }
}
