<?php

namespace Drupal\webhooks;

use Drupal\Component\Uuid\Php as Uuid;
use Drupal\webhooks\Exception\WebhookMismatchSignatureException;

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
   * The unique id.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The event.
   *
   * @var string
   */
  protected $event;

  /**
   * The content type.
   *
   * @var string
   */
  protected $contentType;

  /**
   * The secret.
   *
   * @var string
   */
  protected $secret;

  /**
   * Processing status.
   *
   * @var bool
   */
  protected $status;

  /**
   * Webhook constructor.
   *
   * @param array $payload
   *   The payload that is being send with the webhook.
   * @param array $headers
   *   The headers that are being send with the webhook.
   * @param string $event
   *   The event that is acted upon.
   * @param string $content_type
   *   The content type of the payload.
   */
  public function __construct(
      array $payload = [],
      array $headers = [],
      $event = 'default',
      $content_type = 'json'
  ) {
    $this->setPayload($payload);
    $this->setHeaders($headers);
    $this->setEvent($event);
    $this->setContentType($content_type);

    $uuid = new Uuid();
    $this->setUuid($uuid->generate());

    // Default to success.
    $this->setStatus(TRUE);
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
  public function setHeaders(array $headers) {
    // RequestStack returns the Header-Value as an array.
    foreach ($headers as $key => $value) {
      if (is_array($value)) {
        $headers[$key] = reset($value);
      }
    }
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
  public function addHeaders(array $headers) {
    $this->headers = array_merge(
      $this->headers,
      $headers
    );
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
   * Set the payload.
   *
   * @param array $payload
   *   A payload array.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setPayload(array $payload) {
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
  public function addPayload(array $payload) {
    $this->payload = array_merge(
      $this->payload,
      $payload
    );
    $this->setSecret($this->secret);
    return $this;
  }

  /**
   * Get the unique id.
   *
   * @return string
   *   the uuid string.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Set the unique id.
   *
   * @param string $uuid
   *   A uuid string.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
    $this->addHeaders(['X-Drupal-Delivery' => $uuid]);
    return $this;
  }

  /**
   * Get the event.
   *
   * @return string
   *   The event string.
   */
  public function getEvent() {
    return $this->event;
  }

  /**
   * Set the event.
   *
   * @param string $event
   *   An event string in the form of entity:entity_type:action,
   *   e.g. 'entity:user:create', 'entity:user:update' or 'entity:user:delete'.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setEvent($event) {
    $this->event = $event;
    $this->addHeaders(
      ['X-Drupal-Event' => $event]
    );
    return $this;
  }

  /**
   * Get the content type.
   *
   * @return string
   *   The content type string.
   */
  public function getContentType() {
    return $this->contentType;
  }

  /**
   * Set the content type.
   *
   * @param string $content_type
   *   A content type string, e.g. 'json' or 'xml'.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setContentType($content_type) {
    $this->contentType = $content_type;
    $this->addHeaders(
      ['Content-Type' => 'application/' . $content_type]
    );
    return $this;
  }

  /**
   * Get the secret.
   *
   * @return string
   *   The secret string.
   */
  public function getSecret() {
    return $this->secret;
  }

  /**
   * Set the secret.
   *
   * @param string $secret
   *   A secret string.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setSecret($secret) {
    $this->secret = $secret;
    return $this;
  }

  /**
   * Retrieve the current Webhook status.
   *
   * @return bool
   *   TRUE indicates no errors, FALSE indicates an error occurred.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Set the status.
   *
   * @param bool $status
   *   New status value.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * Get the payload signature from headers.
   *
   * @return string
   *   The signature string, e.g. sha1=de7c9b85b8b78aa6bc8a7a36f70a90701c9db4d9
   */
  public function getSignature() {
    $headers = $this->getHeaders();
    foreach ($headers as $key => $value) {
      if (strtolower($key) === 'x-hub-signature') {
        return $value;
      }
    }
    return '';
  }

  /**
   * Set the payload signature.
   *
   * @param string $body
   *   The encoded request body.
   *
   * @return Webhook
   *   The webhook.
   */
  public function setSignature($body) {
    $this->addHeaders(
      [
        'X-Hub-Signature' => 'sha1=' . hash_hmac('sha1', $body, $this->secret, FALSE),
      ]
    );
    return $this;
  }

  /**
   * Verify the webhook with the stored secret.
   *
   * @param string $secret
   *   The webhook secret.
   * @param string $payload
   *   The raw webhook payload.
   * @param string $signature
   *   The webhook signature.
   *
   * @return bool
   *   Boolean TRUE for success.
   *
   * @throws \Drupal\webhooks\Exception\WebhookMismatchSignatureException
   *   Throws exception if signatures do not match.
   */
  public static function verify($secret, $payload, $signature) {
    [$algorithm, $user_string] = explode('=', $signature);
    $known_string = hash_hmac($algorithm, $payload, $secret);
    if (!hash_equals($known_string, $user_string)) {
      throw new WebhookMismatchSignatureException($user_string, $algorithm . '=' . $known_string, $payload);
    }
    return TRUE;
  }

}
