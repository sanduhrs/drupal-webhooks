<?php

namespace Drupal\webhooks;
use GuzzleHttp\Client;


/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhookService implements WebhookServiceInterface {

  /**
   * Constructor.
   */
  public function __construct(Client $client) {

  }
}
