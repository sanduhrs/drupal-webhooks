<?php

namespace Drupal\webhooks;
use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
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
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /** @var  \Symfony\Component\EventDispatcher */
  protected $eventDispatcher;

  /**
   * Constructor.
   */
  public function __construct(Client $client, EventDispatcher $eventDispatcher) {
    $this->client = $client;
    $this->eventDispatcher = $eventDispatcher;
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
          urlencode();
        break;

      default :
        _drupal_log_error('None supported CONTENT_TYPE (json, xml , x-www-form');
        break;
    }
  }

}
