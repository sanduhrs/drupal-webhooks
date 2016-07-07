<?php

namespace Drupal\webhooks\Event;


final class WebhookEvents {
  /**
   * @Event
   *
   * Name of the event fired when a webhook is sent.
   */
  const SEND = 'webhook.send';

  /**
   * @Event
   *
   * Name of the event fired when a webhook is received.
   */
  const RECEIVE = 'webhook.receive';
}