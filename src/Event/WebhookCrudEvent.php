<?php

namespace Drupal\webhooks\Event;


use Drupal\webhooks\WebhookService;
use Symfony\Component\EventDispatcher\Event;

class WebhookCrudEvent extends Event {

  protected $webhook;

  public function __construct(WebhookService $webhook) {
    $this->webhook = $webhook;
  }

  public function getWebhook() {
    return $this->webhook;
  }


}
