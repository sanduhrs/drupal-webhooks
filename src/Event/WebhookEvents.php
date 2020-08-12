<?php

namespace Drupal\webhooks\Event;

/**
 * Class WebhookEvents.
 *
 * @package Drupal\webhooks\Event
 */
final class WebhookEvents {

  /**
   * Name of the event fired when a webhook is sent.
   *
   * This event allows modules to perform an action whenever a webhook is sent.
   * The event listener method receives a \Drupal\webhooks\Event\SendEvent
   * instance.
   *
   * @Event
   */
  const SEND = 'webhook.send';

  /**
   * Name of the event fired when a webhook is received.
   *
   * This event allows modules to perform an action whenever a webhook is
   * received.
   * The event listener method receives a \Drupal\webhooks\Event\ReceiveEvent
   * instance.
   *
   * @Event
   */
  const RECEIVE = 'webhook.receive';

}
