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
    switch ($webhook->getEntityType()) {
      case 'user':
        $payload->setPayload(User::load($webhook->id()));
        break;
      case 'node':
        $payload->setPayload(Node::load($webhook->id()));
        break;
      case 'comment':
        $payload->setPayload(Comment::load($webhook->id()));
        break;
    }
    $this->client->post($webhook->getPayloadUrl(),$payload->getPayload());
  }

  public function receive() {
    $_POST[''];
  }
  /*// Send
$event = new WebhookSend($this);
$this->eventDispatcher->dispatch(WebhookEvents::SEND, $event);
  // Receive
$event = new WebhookReceive($this);
$this->eventDispatcher->dispatch(WebhookEvents::RECEIVE, $event);*/
}
