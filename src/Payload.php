<?php

namespace Drupal\webhooks;

/**
 * Class Payload.
 *
 * @package Drupal\webhooks
 */
class Payload {

  /**
   * The payload.
   *
   * @var array
   */
  protected $payload;

  /**
   * Payload constructor.
   *
   * @param array $payload
   *   A payload array.
   */
  public function __construct($payload) {
    $this->payload = $payload;
  }

  /**
   * Get payload array.
   *
   * @return array
   *   The payload array.
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * Set a payload array.
   *
   * @param array $payload
   *   A payload array.
   */
  public function setPayload($payload) {
    $this->payload = $payload;
  }

  /**
   * Add element to payload.
   *
   * @param array $payload_element
   *   A payload array element to add.
   */
  public function addToPayload($payload_element) {
    array_push($this->payload, $payload_element);
  }

}
