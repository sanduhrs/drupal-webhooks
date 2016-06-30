<?php

namespace Drupal\webhooks\Event;


final class WebhookEvents {
  /**
   * Name of the event fired when a webhook is sent.
   */
  const SEND = 'webhook.send';

  /**
   * Name of the event fired when a webhook is received.
   */
  const RECEIVE = 'webhook.receive';
}
