<?php

namespace Drupal\webhooks;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webhooks\Entity\Webhook as WebhookConfig;
use Drupal\webhooks\Entity\WebhookInterface;
use Drupal\webhooks\Webhook;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhookService implements WebhookServiceInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  protected $requestStack;

  protected $webhook;

  /**
   * WebhookService constructor.
   *
   * @param \GuzzleHttp\Client $client
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
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
   * @param \Drupal\webhooks\Entity\Webhook $webhook
   * @param \Drupal\webhooks\Payload $payload
   */
  public function send(WebhookConfig $webhook, Payload $payload) {
    $string = self::encode(
      $payload->getPayload(),
      $webhook->getContentType()
    );
    try {
      $this->client->post(
        $webhook->getPayloadUrl(),
        [
          'body' => $string,
          'headers' => [
            'Content-Type' => 'application/' . $webhook->getContentType(),
            'X-Drupal-Webhooks-Event' => '',
            'X-Drupal-Webhooks-Delivery' => '',
          ],
        ]
      );
    } catch (\Exception $e) {
      $this->loggerFactory->get('webhooks')->error(
        'Could not send Webhook @webhook: @message',
        ['@webhook' => $webhook->id(), '@message' => $e->getMessage()]
      );
    }

    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher */
    $eventDispatcher = \Drupal::service('event_dispatcher');
    $eventDispatcher->dispatch(
      WebhookEvents::SEND,
      $webhook,
      $payload
    );
  }

  /**
   * @return \Drupal\webhooks\Webhook
   */
  public function receive() {
    $request = $this->requestStack->getCurrentRequest();
    $headers = \GuzzleHttp\Psr7\parse_header($request->headers->all());
    $payload = WebhookService::decode(
      $request->getContent(),
      $request->getContentType()
    );
    return new Webhook(
      $headers,
      $payload
    );
  }

  /**
   * @param $data
   * @param $content_type
   * @return mixed
   */
  public static function encode($data, $content_type) {
    /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
    $encoder = \Drupal::service('serializer.encoder.' . $content_type);
    if (!empty($encoder) && $encoder->supportsEncoding($content_type)) {
      return $encoder->encode($data, $content_type);
    }
    return '';
  }

  /**
   * @param $data
   * @param $content_type
   * @return mixed
   */
  public static function decode($data, $content_type) {
    /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
    $encoder = \Drupal::service('serializer.encoder.' . $content_type);
    if (!empty($encoder) && $encoder->supportsDecoding($content_type)) {
      return $encoder->decode($data, $content_type);
    }
    return '';
  }
}
