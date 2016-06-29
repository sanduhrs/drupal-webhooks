<?php

namespace Drupal\webhooks;
use GuzzleHttp\Client;
use Drupal\webhooks\Payload;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhookService implements WebhookServiceInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Constructor.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * @param \Drupal\webhooks\Webhook $webhook
   * @param \Drupal\webhooks\Payload $payload
   */
  public function send(Webhook $webhook, Payload $payload) {

  }

  public function receive() {

  }
}
