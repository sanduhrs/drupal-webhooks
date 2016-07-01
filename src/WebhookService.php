<?php

namespace Drupal\webhooks;
use Drupal\webhooks\Entity\Webhook;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhookService implements WebhookServiceInterface {

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
  public function send(Webhook $webhook, Payload $payload) {
    $this->client->post($webhook->getPayloadUrl(),$payload->getPayload());
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
        _drupal_log_error('None supported CONTENT_TYPE (json, xml , x-www-form');
        break;
    }
  }

}
