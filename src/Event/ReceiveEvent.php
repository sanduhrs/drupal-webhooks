<?php

namespace Drupal\webhooks\Event;

use Drupal\webhooks\Webhook;
use Symfony\Component\EventDispatcher\Event;

class ReceiveEvent extends Event {

  /**
   * @var \Drupal\webhooks\Webhook
   */
  protected $webhook;

  /**
   * Receive constructor.
   *
   * @var \Drupal\webhooks\Webhook $webhook
   */
  public function __construct(Webhook $webhook) {
    $this->webhook = $webhook;
  }

  /**
   * @return \Drupal\webhooks\Webhook
   */
  public function getWebhook() {
    return $this->webhook;
  }

}
