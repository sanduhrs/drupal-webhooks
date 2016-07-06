<?php

namespace Drupal\webhooks\Event;


use Drupal\webhooks\WebhookService;
use Symfony\Component\EventDispatcher\Event;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;

class Receive extends Event {

  protected $request;

  protected $headers;

  protected $body;

  protected $payload;

  /**
   * Receive constructor.
   * @param \GuzzleHttp\Psr7\ServerRequest $request
   */
  public function __construct(ServerRequest $request) {
    $this->request = $request;
    $this->headers = \GuzzleHttp\Psr7\parse_header($request->getHeaders());
    $this->body = $request->getBody()->getContents();


    $this->payload = $array;
  }

  public function getWebhook() {
    return $this->receive;
  }


}
