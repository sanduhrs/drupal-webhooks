<?php

namespace Drupal\webhooks;

use Drupal\Component\Uuid\Php as Uuid;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webhooks\Entity\WebhookConfig;
use Drupal\webhooks\Event\WebhookEvents;
use Drupal\webhooks\Event\ReceiveEvent;
use Drupal\webhooks\Event\SendEvent;
use Drupal\webhooks\Exception\WebhookIncomingEndpointNotFoundException;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhooksService implements WebhookDispatcherInterface, WebhookReceiverInterface {

  use StringTranslationTrait;

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
   * The Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The webhook storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $webhookStorage;

  /**
   * WebhooksService constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   The http client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
      Client $client,
      LoggerChannelFactoryInterface $logger_factory,
      RequestStack $request_stack,
      EventDispatcherInterface $event_dispatcher,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->client = $client;
    $this->logger = $logger_factory->get('webhooks');
    $this->requestStack = $request_stack;
    $this->eventDispatcher = $event_dispatcher;
    $this->webhookStorage = $entity_type_manager->getStorage('webhook_config');
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByEvent($event, $type = 'outgoing') {
    $query = $this->webhookStorage->getQuery()
      ->condition('status', 1)
      ->condition('events', $event, 'CONTAINS')
      ->condition('type', $type, '=');
    $ids = $query->execute();
    return $this->webhookStorage
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
      $this->logger->error(
        'Dispatch Failed. Subscriber %subscriber on Webhook %uuid for Event %event: @message', [
          '%subscriber' => $webhook_config->id(),
          '%uuid' => $webhook->getUuid(),
          '%event' => $webhook->getEvent(),
          '@message' => $e->getMessage(),
          'link' => Link::createFromRoute(
            $this->t('Edit Webhook'),
            'entity.webhook_config.edit_form', [
              'webhook_config' => $webhook_config->id(),
            ]
          )->toString(),
        ]
      );
      $webhook->setStatus(FALSE);
    }

    // Dispatch Webhook Send event.
    $this->eventDispatcher->dispatch(
      WebhookEvents::SEND,
      new SendEvent($webhook_config, $webhook)
    );

    // Log the sent webhook.
    $this->logger->info(
      'Webhook Dispatched. Subscriber %subscriber on Webhook %uuid for Event %event. Payload: @payload', [
        '%subscriber' => $webhook_config->id(),
        '%uuid' => $webhook->getUuid(),
        '%event' => $webhook->getEvent(),
        '@payload' => json_encode($webhook->getPayload()),
        'link' => Link::createFromRoute(
          $this->t('Edit Webhook'),
          'entity.webhook_config.edit_form', [
            'webhook_config' => $webhook_config->id(),
          ]
        )->toString(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function receive($name) {
    // We only receive webhook requests when a webhook configuration exists
    // with a matching machine name.
    $query = $this->webhookStorage->getQuery()
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

    $webhook = new Webhook($payload, $request->headers->all());

    /** @var \Drupal\webhooks\Entity\WebhookConfig $webhook_config */
    $webhook_config = $this->webhookStorage
      ->load($name);

    // Verify in both cases: the webhook_config contains a secret
    // and/or the webhook contains a signature.
    if ($webhook_config->getSecret() || $webhook->getSignature()) {
      $webhook->verify();
    }

    // Dispatch Webhook Receive event.
    $this->eventDispatcher->dispatch(
      WebhookEvents::RECEIVE,
      new ReceiveEvent($webhook)
    );

    if (!$webhook->getStatus()) {
      $this->logger->warning(
        'Processing Failure. Subscriber %subscriber on Webhook %uuid for Event %event. Payload: @webhook', [
          '%subscriber' => $webhook_config->id(),
          '%uuid' => $webhook->getUuid(),
          '%event' => $webhook->getEvent(),
          '@payload' => json_encode($webhook->getPayload()),
          'link' => Link::createFromRoute(
            $this->t('Edit Webhook'),
            'entity.webhook_config.edit_form', [
              'webhook_config' => $webhook_config->id(),
            ]
          )->toString(),
        ]
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
  protected static function encode(array $data, $content_type) {
    try {
      $serializer = \Drupal::service('serializer');
      \assert($serializer instanceof SerializerInterface);
      return $serializer->serialize($data, $content_type);
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
  protected static function decode(array $data, $format) {
    try {
      $serializer = \Drupal::service('serializer');
      \assert($serializer instanceof SerializerInterface);
      return $serializer->deserialize($data, $format);
    }
    catch (\Exception $e) {
    }
    return '';
  }

}
