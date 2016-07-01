<?php

namespace Drupal\webhooks;

use Drupal\webhooks\Entity\Webhook;
use Drupal\webhooks\Entity\WebhookInterface;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhookService extends Webhook implements WebhookServiceInterface {

  /**
   * @var \Drupal::httpClient()
   */
  protected $client;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->client = \Drupal::httpClient();
  }

  /**
   * @param \Drupal\webhooks\Webhook $webhook
   * @param \Drupal\webhooks\Payload $payload
   */
  public function send(Payload $payload) {
    $this->client->post($this->getPayloadUrl(),$payload->getPayload());
  }

  public function receive() {
    switch($_SERVER['CONTENT_TYPE']) {
      case 'application/json':
          \GuzzleHttp\json_decode();
        break;
      case 'application/xml':
          xmlrpc_decode();
        break;
      case 'x-www-form-urlencoded':
          urldecode();
        break;

      default :
        \Drupal::logger('webhooks')->error('None supported CONTENT_TYPE was recognized.');
        break;
    }
  }

}
