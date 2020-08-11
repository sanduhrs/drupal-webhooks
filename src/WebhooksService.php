<?php

namespace Drupal\webhooks;

use Drupal\Component\Uuid\UuidInterface;
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
use Symfony\Component\Serializer\Serializer;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhooksService implements WebhookDispatcherInterface, WebhookReceiverInterface, WebhookSerializerInterface {

  use StringTranslationTrait;

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
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The Uuid service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

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
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The Uuid service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
      Client $client,
      LoggerChannelFactoryInterface $logger_factory,
      RequestStack $request_stack,
      EventDispatcherInterface $event_dispatcher,
      EntityTypeManagerInterface $entity_type_manager,
      Serializer $serializer,
      UuidInterface $uuid
  ) {
    $this->client = $client;
    $this->logger = $logger_factory->get('webhooks');
    $this->requestStack = $request_stack;
    $this->eventDispatcher = $event_dispatcher;
    $this->webhookStorage = $entity_type_manager->getStorage('webhook_config');
    $this->serializer = $serializer;
    $this->uuid = $uuid;
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
  public function triggerEvent(Webhook $webhook, $event) {
    /** @var \Drupal\webhooks\WebhooksService $webhooks_service */
    $webhooks_service = \Drupal::service('webhooks.service');
    // Load all webhooks for the occurring event.
    /** @var \Drupal\webhooks\Entity\WebhookConfig $webhook_config */
    $webhook_configs = $this->loadMultipleByEvent($event);

    foreach ($webhook_configs as $webhook_config) {
      // Send the Webhook object.
      $webhooks_service->send($webhook_config, $webhook);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function send(WebhookConfig $webhook_config, Webhook $webhook) {
    $webhook->setUuid($this->uuid->generate());
    $body = $this->encode(
      $webhook->getPayload(),
      $webhook->getMimeSubType()
    );
    if ($secret = $webhook_config->getSecret()) {
      $webhook->setSecret($secret);
      $webhook->setSignature($body);
    }

    try {
      $this->client->post(
        $webhook_config->getPayloadUrl(),
        [
          'headers' => $webhook->getHeaders(),
          'body' => $body,
          // Workaround for blocking local to local requests,
          // there will be 'Dispatch Failed' errors in the logs.
          'timeout' => (strpos($webhook_config->getPayloadUrl(), \Drupal::request()->getHost())) ? 0.1 : 0,
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
        '@payload' => $this->encode($webhook->getPayload(), $webhook->getMimeSubType()),
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
    $payload = $this->decode(
      $request->getContent(),
      $request->getContentType()
    );

    $webhook = new Webhook(
      $payload,
      $request->headers->all(),
      '',
      $request->headers->get('Content-Type')
    );
    $webhook->setUuid($request->headers->get('X-Drupal-Delivery'));
    $webhook->setEvent($request->headers->get('X-Drupal-Event'));

    /** @var \Drupal\webhooks\Entity\WebhookConfig $webhook_config */
    $webhook_config = $this->webhookStorage
      ->load($name);

    // Verify in both cases: the webhook_config contains a secret
    // and/or the webhook contains a signature.
    if ($webhook_config->getSecret() || $webhook->getSignature()) {
      Webhook::verify($webhook_config->getSecret(), $request->getContent(), $webhook->getSignature());
    }

    // Dispatch Webhook Receive event.
    $this->eventDispatcher->dispatch(
      WebhookEvents::RECEIVE,
      new ReceiveEvent($webhook_config, $webhook)
    );

    if (!$webhook->getStatus()) {
      $this->logger->warning(
        'Processing Failure. Subscriber %subscriber on Webhook %uuid for Event %event. Payload: @payload', [
          '%subscriber' => $webhook_config->id(),
          '%uuid' => $webhook->getUuid(),
          '%event' => $webhook->getEvent(),
          '@payload' => $this->encode($webhook->getPayload(), $webhook->getMimeSubType()),
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
   * Set the serializer to use when normalizing/encoding an object.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   */
  public function setSerializer(Serializer $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    return $this->serializer->encode($data, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format, array $context = []) {
    return $this->serializer->supportsEncoding($format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    return $this->serializer->decode($data, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format, array $context = []) {
    return $this->serializer->supportsDecoding($format, $context);
  }

}
