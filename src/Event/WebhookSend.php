<?php

namespace Drupal\webhooks\Event;


use Drupal\webhooks\WebhookService;
use Symfony\Component\EventDispatcher\Event;

class WebhookSend extends Event {

  protected $send;

  public function __construct(WebhookService $webhook) {
    $this->send = $webhook;
  }

  public function getWebhook() {
    return $this->send;
  }


}
