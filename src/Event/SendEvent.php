<?php

namespace Drupal\webhooks\Event;

use Drupal\webhooks\Entity\WebhookConfig;
use Symfony\Component\EventDispatcher\Event;

class SendEvent extends Event {

  /**
   * @var \Drupal\webhooks\Entity\WebhookConfig
   */
  protected $webhookConfig;

  /**
   * SendEvent constructor.
   *
   * @param \Drupal\webhooks\Entity\WebhookConfig $webhook_config
   */
  public function __construct(WebhookConfig $webhook_config) {
    $this->webhookConfig = $webhook_config;
  }

  /**
   * @return \Drupal\webhooks\Entity\WebhookConfig
   */
  public function getWebhookConfig() {
    return $this->webhookConfig;
  }

}
