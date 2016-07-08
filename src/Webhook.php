<?php

namespace Drupal\webhooks;

/**
 * Class Webhook.
 *
 * @package Drupal\webhooks
 */
class Webhook {

  /**
   * The webhooks headers.
   *
   * @var array
   */
  protected $headers;

  /**
   * The webhook payload.
   *
   * @var array
   */
  protected $payload;

  /**
   * Webhook constructor.
   *
   * @param array $headers
   *   The headers that are being send with the webhook.
   * @param array $payload
   *   The payload that is being send with the webhook.
   */
  public function __construct($headers, $payload) {
    $this->headers = $headers;
    $this->payload = $payload;
  }

  /**
   * Get the headers.
   *
   * @return array
   *   The headers array.
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Set the headers.
   *
   * @param array $headers
   *   A headers array.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setHeaders($headers) {
    $this->headers = $headers;
    return $this;
  }

  /**
   * Add to the headers.
   *
   * @param array $headers
   *   A headers array.
   *
   * @return Webhook
   *   The webhook.
   */
  public function addHeaders($headers) {
    $this->headers = array_merge($this->headers, $headers);
    return $this;
  }

  /**
   * Get the payload.
   *
   * @return array
   *   The payload array.
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * Set te payload.
   *
   * @param array $payload
   *   A payload array.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setPayload($payload) {
    $this->payload = $payload;
    return $this;
  }

  /**
   * Add to the payload.
   *
   * @param array $payload
   *   A payload array.
   *
   * @return Webhook
   *   The webhook.
   */
  public function addPayload($payload) {
    array_push($this->payload, $payload);
    return $this;
  }

}
