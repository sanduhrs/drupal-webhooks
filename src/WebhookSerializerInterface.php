<?php

namespace Drupal\webhooks;

/**
 * Webhook receivers catch incoming events and trigger an internal event.
 *
 * The internal event allows any module in the Drupal site to react to remote
 * operations.
 *
 * @package Drupal\webhooks
 */
interface WebhookSerializerInterface {

  /**
   * Encode payload data.
   *
   * @param array $data
   *   The payload data array.
   * @param string $format
   *   The content type string, e.g. json, xml.
   *
   * @return string
   *   A string suitable for a http request.
   */
  public function encode(array $data, $format);

  /**
   * Decode payload data.
   *
   * @param string $data
   *   The payload data array.
   * @param string $format
   *   The format string, e.g. json, xml.
   *
   * @return mixed
   *   An object suitable for php usage.
   */
  public function decode($data, $format);

}
