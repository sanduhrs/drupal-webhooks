<?php

namespace Drupal\webhooks;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Uuid\Php as Uuid;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webhooks\Entity\WebhookConfig;
use Drupal\webhooks\Event\WebhookEvents;
use Drupal\webhooks\Event\ReceiveEvent;
use Drupal\webhooks\Event\SendEvent;
use Drupal\webhooks\Exception\WebhookIncomingEndpointNotFoundException;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhooksService implements WebhookDispatcherInterface, WebhookReceiverInterface {

  /**
   * The Json format.
   */
  const CONTENT_TYPE_JSON = 'json';

  /**
   * The Xml format.
   */
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
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The query factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
      Client $client,
      LoggerChannelFactoryInterface $logger_factory,
      RequestStack $request_stack,
      ContainerAwareEventDispatcher $event_dispatcher,
      QueryFactory $query_factory,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->client = $client;
    $this->loggerFactory = $logger_factory;
    $this->requestStack = $request_stack;
    $this->eventDispatcher = $event_dispatcher;
    $this->queryFactory = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByEvent($event, $type = 'outgoing') {
    $query = $this->queryFactory->get('webhook_config')
      ->condition('status', 1)
      ->condition('events', $event, 'CONTAINS')
      ->condition('type', $type, '=');
    $ids = $query->execute();
    return $this->entityTypeManager->getStorage('webhook_config')
      ->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function send(WebhookConfig $webhook_config, Webhook $webhook) {
    $uuid = new Uuid();
    $webhook->setUuid($uuid->generate());
    if ($secret = $webhook_config->getSecret()) {
      $webhook->setSecret($secret);
      $webhook->setSignature();
    }

    $body = static::encode(
      $webhook->getPayload(),
      $webhook_config->getContentType()
    );

    try {
      $this->client->post(
        $webhook_config->getPayloadUrl(),
        [
          'headers' => $webhook->getHeaders(),
          'body' => $body,
        ]
      );
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('webhooks')->error(
        'Could not send Webhook "@webhook": @message',
        ['@webhook' => $webhook_config->id(), '@message' => $e->getMessage()]
      );
      $webhook->setStatus(FALSE);
    }

    // Dispatch Webhook Send event.
    $this->eventDispatcher->dispatch(
      WebhookEvents::SEND,
      new SendEvent($webhook_config, $webhook)
    );

    // Log the sent webhook.
    $this->loggerFactory->get('webhooks')->info(
      'Completed webhook dispatch: <code><pre>@webhook</pre></code>',
      ['@webhook' => print_r($webhook, TRUE)]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function receive($name) {
    // We only receive webhook requests when a webhook configuration exists
    // with a matching machine name.
    $query = $this->queryFactory->get('webhook_config')
      ->condition('id', $name)
      ->condition('type', 'incoming')
      ->condition('status', 1);
    $ids = $query->execute();
    if (!array_key_exists($name, $ids)) {
      throw new WebhookIncomingEndpointNotFoundException($name);
    }

    $request = $this->requestStack->getCurrentRequest();
    $payload = static::decode(
      $request->getContent(),
      $request->getContentType()
    );

    /** @var \Drupal\webhooks\Webhook $webhook */
    $webhook = new Webhook($payload, $request->headers->all());
    $signature = $webhook->getSignature();

    /** @var \Drupal\webhooks\Entity\WebhookConfig $webhook_config */
    $webhook_config = $this->entityTypeManager->getStorage('webhook_config')
      ->load($name);
    if ($webhook_config->getSecret()) {
      $webhook->verify();
    }

    // Dispatch Webhook Receive event.
    $this->eventDispatcher->dispatch(
      WebhookEvents::RECEIVE,
      new ReceiveEvent($webhook)
    );

    if (!$webhook->getStatus()) {
      $this->loggerFactory->get('webhooks')->warning(
        'Something went wrong processing webhook: <code><pre>@webhook</pre></code>',
        ['@webhook' => print_r($webhook, TRUE)]
      );
    }

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
  protected static function encode($data, $content_type) {
    try {
      /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
      $encoder = \Drupal::service('serializer.encoder.' . $content_type);
      if (!empty($encoder) && $encoder->supportsEncoding($content_type)) {
        return $encoder->encode($data, $content_type);
      }
    }
    catch (\Exception $e) {
    }
    return '';
  }

  /**
   * Decode payload data.
   *
   * @param array $data
   *   The payload data array.
   * @param string $format
   *   The format string, e.g. json, xml.
   *
   * @return mixed
   *   A string suitable for php usage.
   */
  protected static function decode($data, $format) {
    try {
      /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
      $encoder = \Drupal::service('serializer.encoder.' . $format);
      if (!empty($encoder) && $encoder->supportsDecoding($format)) {
        return $encoder->decode($data, $format);
      }
    }
    catch (\Exception $e) {
    }
    return '';
  }

}
