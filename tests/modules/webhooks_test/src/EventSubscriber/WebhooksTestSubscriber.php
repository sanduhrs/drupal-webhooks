<?php

namespace Drupal\webhooks_test\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\webhooks\Event\ReceiveEvent;
use Drupal\webhooks\Event\SendEvent;
use Drupal\webhooks\Event\WebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Webhooks Test event subscriber.
 */
class WebhooksTestSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * Webhook send event handler.
   *
   * @param \Drupal\webhooks\Event\SendEvent $event
   *   Response event.
   */
  public function onWebhookSend(SendEvent $event) {
    \Drupal::state()->set(__FUNCTION__, TRUE);

    $webhook = $event->getWebhook();
    \Drupal::state()->set(__FUNCTION__ . '_webhook', $webhook);

    $webhook_config = $event->getWebhookConfig();
    \Drupal::state()->set(__FUNCTION__ . '_webhook_config', $webhook_config);
  }

  /**
   * Webhook receive event handler.
   *
   * @param \Drupal\webhooks\Event\ReceiveEvent $event
   *   Response event.
   */
  public function onWebhookReceive(ReceiveEvent $event) {
    \Drupal::state()->set(__FUNCTION__, TRUE);

    $webhook = $event->getWebhook();
    \Drupal::state()->set(__FUNCTION__ . '_webhook', $webhook);

    $webhook_config = $event->getWebhookConfig();
    \Drupal::state()->set(__FUNCTION__ . '_webhook_config', $webhook_config);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      WebhookEvents::SEND => ['onWebhookSend'],
      WebhookEvents::RECEIVE => ['onWebhookReceive'],
    ];
  }

}
