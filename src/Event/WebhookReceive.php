<?php

namespace Drupal\webhooks\Event;


use Drupal\webhooks\WebhookService;
use Symfony\Component\EventDispatcher\Event;

class WebhookReceive extends Event {

  protected $receive;

  public function __construct($receive) {
    $this->receive = $receive;
  }

  public function getWebhook() {
    return $this->receive;
  }


}
