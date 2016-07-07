<?php

namespace Drupal\webhooks;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webhooks\Entity\WebhookConfig;
use Drupal\webhooks\Event\WebhookEvents;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\webhooks\Event\ReceiveEvent;
use Drupal\webhooks\Event\SendEvent;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhookService implements WebhookServiceInterface {

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

  /**
   * WebhookService constructor.
   *
   * @param \GuzzleHttp\Client $client
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(
      Client $client,
      LoggerChannelFactoryInterface $logger_factory,
      RequestStack $request_stack
  ) {
    $this->client = $client;
    $this->loggerFactory = $logger_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Send a webhook.
   *
   * @param \Drupal\webhooks\Entity\WebhookConfig $webhook_config
   * @param \Drupal\webhooks\Webhook $webhook
   */
  public function send(WebhookConfig $webhook_config, Webhook $webhook) {
    $headers = $webhook->getHeaders();
    $body = self::encode(
      $webhook->getPayload(),
      $webhook_config->getContentType()
    );

    try {
      $this->client->post(
        $webhook_config->getPayloadUrl(),
        ['headers' => $headers, 'body' => $body]
      );
    } catch (\Exception $e) {
      $this->loggerFactory->get('webhooks')->error(
        'Could not send Webhook @webhook: @message',
        ['@webhook' => $webhook_config->id(), '@message' => $e->getMessage()]
      );
    }

    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher */
    $eventDispatcher = \Drupal::service('event_dispatcher');
    $eventDispatcher->dispatch(
      WebhookEvents::SEND,
      new SendEvent($webhook_config, $webhook)
    );

    $this->loggerFactory->get('webhooks')->info(
      'Sent a Webhook: <code><pre>@webhook</pre></code>',
      [
        '@webhook' => print_r($webhook, true)
      ]
    );
  }

  /**
   * Receive a webhook.
   *
   * @return \Drupal\webhooks\Webhook
   */
  public function receive() {
    $request = $this->requestStack->getCurrentRequest();
    $headers = $request->headers->all();
    $payload = WebhookService::decode(
      $request->getContent(),
      $request->getContentType()
    );

    /** @var \Drupal\webhooks\Webhook $webhook */
    $webhook = new Webhook($headers, $payload);

    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher */
    $eventDispatcher = \Drupal::service('event_dispatcher');
    $eventDispatcher->dispatch(
      WebhookEvents::RECEIVE,
      new ReceiveEvent($webhook)
    );

    $this->loggerFactory->get('webhooks')->info(
      'Received a Webhook: <code><pre>@webhook</pre></code>',
      [
        '@webhook' => print_r($webhook, true)
      ]
    );

    return $webhook;
  }

  /**
   * Encode a payload.
   *
   * @param $data
   * @param $content_type
   * @return mixed
   */
  public static function encode($data, $content_type) {
    try {
      /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
      $encoder = \Drupal::service('serializer.encoder.' . $content_type);
      if (!empty($encoder) && $encoder->supportsEncoding($content_type)) {
        return $encoder->encode($data, $content_type);
      }
    }
    catch (\Exception $e) {
      return '';
    }
  }

  /**
   * Decode a payload.
   *
   * @param $data
   * @param $content_type
   * @return mixed
   */
  public static function decode($data, $content_type) {
    try {
      /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
      $encoder = \Drupal::service('serializer.encoder.' . $content_type);
      if (!empty($encoder) && $encoder->supportsDecoding($content_type)) {
        return $encoder->decode($data, $content_type);
      }
    }
    catch (\Exception $e) {
      return '';
    }
  }

}
