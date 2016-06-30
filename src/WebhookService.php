<?php

namespace Drupal\webhooks;
use Drupal\webhooks\Event\WebhookCrudEvent;
use Drupal\webhooks\Event\WebhookEvents;
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
    $event = new WebhookCrudEvent($this);
    $this->eventDispatcher->dispatch(WebhookEvents::SEND, $event);
  }

  public function receive() {

  }
}
