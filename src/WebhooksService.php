<?php

namespace Drupal\webhooks;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webhooks\Entity\WebhookConfig;
use Drupal\webhooks\Event\WebhookEvents;
use Drupal\webhooks\Event\ReceiveEvent;
use Drupal\webhooks\Event\SendEvent;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhooksService implements WebhooksServiceInterface {

  const CONTENT_TYPE_JSON = 'json';

  const CONTENT_TYPE_XML = 'xml';

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The webhook container object.
   *
   * @var \Drupal\webhooks\Webhook
   */
  protected $webhook;

  protected $eventDispatcher;

  /**
   * WebhookService constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   A http client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A logger channel factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
      Client $client,
      LoggerChannelFactoryInterface $logger_factory,
      RequestStack $request_stack,
      ContainerAwareEventDispatcher $event_dispatcher
  ) {
    $this->client = $client;
    $this->loggerFactory = $logger_factory;
    $this->requestStack = $request_stack;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Send a webhook.
   *
   * @param \Drupal\webhooks\Entity\WebhookConfig $webhook_config
   *   A webhook config entity.
   * @param \Drupal\webhooks\Webhook $webhook
   *   A webhook object.
   */
  public function send(WebhookConfig $webhook_config, Webhook $webhook) {
    $body = self::encode(
      $webhook->getPayload(),
      $webhook_config->getContentType()
    );

    if (!empty($secret = $webhook_config->getSecret())) {
      $signature = [
        'X-Drupal-Webhooks-Signature' => base64_encode(
          hash_hmac('sha256', $body, $secret, TRUE)
        ),
      ];
      $webhook->addHeaders($signature);
    }

    $headers = $webhook->getHeaders();

    try {
      $this->client->post(
        $webhook_config->getPayloadUrl(),
        ['headers' => $headers, 'body' => $body]
      );
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('webhooks')->error(
        'Could not send Webhook @webhook: @message',
        ['@webhook' => $webhook_config->id(), '@message' => $e->getMessage()]
      );
    }

    // Dispatch Webhook Send event.
    $this->eventDispatcher->dispatch(
      WebhookEvents::SEND,
      new SendEvent($webhook_config, $webhook)
    );

    // Log the sent webhook.
    $this->loggerFactory->get('webhooks')->info(
      'Sent a Webhook: <code><pre>@webhook</pre></code>',
      ['@webhook' => print_r($webhook, TRUE)]
    );
  }

  /**
   * Receive a webhook.
   *
   * @return \Drupal\webhooks\Webhook
   *   A webhook object.
   */
  public function receive() {
    $request = $this->requestStack->getCurrentRequest();
    $headers = $request->headers->all();
    $payload = WebhooksService::decode(
      $request->getContent(),
      $request->getContentType()
    );

    /** @var \Drupal\webhooks\Webhook $webhook */
    $webhook = new Webhook($headers, $payload);

    // Dispatch Webhook Receive event.
    $this->eventDispatcher->dispatch(
      WebhookEvents::RECEIVE,
      new ReceiveEvent($webhook)
    );

    // Log the received webhook.
    $this->loggerFactory->get('webhooks')->info(
      'Received a Webhook: <code><pre>@webhook</pre></code>',
      ['@webhook' => print_r($webhook, TRUE)]
    );

    return $webhook;
  }

  /**
   * Encode payload data.
   *
   * @param array $data
   *   The payload data array.
   * @param string $content_type
   *   The content type string, e.g. json, xml.
   *
   * @return string
   *   A string suitable for a http request.
   */
  public static function encode($data, $content_type) {
    try {
      /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
      $encoder = \Drupal::service('serializer.encoder.' . $content_type);
      if (!empty($encoder) && $encoder->supportsEncoding($content_type)) {
        return $encoder->encode($data, $content_type);
      }
    }
    catch (\Exception $e) {}
    return '';
  }

  /**
   * Decode payload data.
   *
   * @param array $data
   *   The payload data array.
   * @param string $content_type
   *   The content type string, e.g. json, xml.
   *
   * @return mixed
   *   A string suitable for php usage.
   */
  public static function decode($data, $content_type) {
    try {
      /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
      $encoder = \Drupal::service('serializer.encoder.' . $content_type);
      if (!empty($encoder) && $encoder->supportsDecoding($content_type)) {
        return $encoder->decode($data, $content_type);
      }
    }
    catch (\Exception $e) {}
    return '';
  }

}
