<?php

namespace Drupal\webhooks;

class Webhook {

  /**
   * @var array
   */
  protected $headers;

  /**
   * @var array
   */
  protected $payload;

  /**
   * Webhook constructor.
   *
   * @param array $headers
   * @param array $payload
   */
  public function __construct($headers, $payload) {
    $this->headers = $headers;
    $this->payload = $payload;
  }

  /**
   * @return array
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * @param array $headers
   * @return Webhook
   */
  public function setHeaders($headers) {
    $this->headers = $headers;
    return $this;
  }

  /**
   * @param array $headers
   * @return Webhook
   */
  public function addHeaders($headers) {
    array_push($this->headers, $headers);
    return $this;
  }

  /**
   * @return array
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * @param array $payload
   * @return Webhook
   */
  public function setPayload($payload) {
    $this->payload = $payload;
    return $this;
  }

}
