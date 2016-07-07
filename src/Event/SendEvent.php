<?php

namespace Drupal\webhooks\Event;

use Drupal\webhooks\Entity\WebhookConfig;
use Drupal\webhooks\Webhook;
use Symfony\Component\EventDispatcher\Event;

class SendEvent extends Event {

  /**
   * @var \Drupal\webhooks\Webhook
   */
  protected $webhook;

  /**
   * @var \Drupal\webhooks\Entity\WebhookConfig
   */
  protected $webhookConfig;

  /**
   * SendEvent constructor.
   *
   * @param \Drupal\webhooks\Entity\WebhookConfig $webhook_config
   * @param \Drupal\webhooks\Webhook $webhook
   */
  public function __construct(
      WebhookConfig $webhook_config,
      Webhook $webhook
  ) {
    $this->webhook = $webhook;
    $this->webhookConfig = $webhook_config;
  }

  /**
   * @return \Drupal\webhooks\Webhook
   */
  public function getWebhook() {
    return $this->webhook;
  }

  /**
   * @return \Drupal\webhooks\Entity\WebhookConfig
   */
  public function getWebhookConfig() {
    return $this->webhookConfig;
  }

}
