<?php

namespace Drupal\Tests\webhooks\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\webhooks\Entity\WebhookConfig;
use Drupal\webhooks\Webhook;

/**
 * Test description.
 *
 * @group webhooks
 */
class WebhooksTest extends BrowserTestBase {

  const WEBHOOK_ID_INCOMING = 'webhook_id_incoming';

  const WEBHOOK_ID_OUTGOING = 'webhook_id_outgoing';

  const WEBHOOK_ID_INCOMING_VERIFIED = 'webhook_id_incoming_verified';

  const WEBHOOK_ID_OUTGOING_VERIFIED = 'webhook_id_outgoing_verified';

  const WEBHOOK_SECRET = 'iepooleiDahF3eimeikooC2iep1ahqua';

  const WEBHOOK_ID_OUTGOING_XML = 'webhook_id_outgoing_xml';

  const WEBHOOK_ID_INCOMING_XML = 'webhook_id_incoming_xml';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webhooks', 'webhooks_test'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * The webhook service.
   *
   * @var \Drupal\webhooks\WebhooksService
   */
  protected $webhookService;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The uuid.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The payload.
   *
   * @var string[]
   */
  protected $payload = ['payload' => 'attribute'];

  /**
   * The headers.
   *
   * @var string[]
   */
  protected $headers = ['header-type' => 'header-value'];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
    $this->state = \Drupal::state();
    $this->uuid = \Drupal::service('uuid');
    $this->webhookService = \Drupal::service('webhooks.service');

    // Create an incoming webhook.
    WebhookConfig::create([
      'id' => self::WEBHOOK_ID_INCOMING,
      'label' => 'Webhook Incoming',
      'uuid' => \Drupal::service('uuid')->generate(),
      'payload_url' => '',
      'type' => 'incoming',
      'events' => [],
      'content_type' => 'application/json',
      'secret' => '',
      'status' => 1,
    ])->save();

    // Create an outgoing webhook.
    WebhookConfig::create([
      'id' => self::WEBHOOK_ID_OUTGOING,
      'label' => 'Webhook Outgoing',
      'uuid' => \Drupal::service('uuid')->generate(),
      'payload_url' => Url::fromRoute('webhooks.webhook_receive', ['incoming_webhook_name' => self::WEBHOOK_ID_INCOMING])->setAbsolute(TRUE)->toString(),
      'type' => 'outgoing',
      'events' => [],
      'content_type' => 'application/json',
      'secret' => '',
      'status' => 1,
    ])->save();

    // Create an incoming webhook.
    WebhookConfig::create([
      'id' => self::WEBHOOK_ID_INCOMING_VERIFIED,
      'label' => 'Webhook Incoming Verified',
      'uuid' => \Drupal::service('uuid')->generate(),
      'payload_url' => '',
      'type' => 'incoming',
      'events' => [],
      'content_type' => 'application/json',
      'secret' => self::WEBHOOK_SECRET,
      'status' => 1,
    ])->save();

    // Create an outgoing webhook.
    WebhookConfig::create([
      'id' => self::WEBHOOK_ID_OUTGOING_VERIFIED,
      'label' => 'Webhook Outgoing Verified',
      'uuid' => \Drupal::service('uuid')->generate(),
      'payload_url' => Url::fromRoute('webhooks.webhook_receive', ['incoming_webhook_name' => self::WEBHOOK_ID_INCOMING_VERIFIED])->setAbsolute(TRUE)->toString(),
      'type' => 'outgoing',
      'events' => [],
      'content_type' => 'application/json',
      'secret' => self::WEBHOOK_SECRET,
      'status' => 1,
    ])->save();

    // Create an incoming webhook.
    WebhookConfig::create([
      'id' => self::WEBHOOK_ID_INCOMING_XML,
      'label' => 'Webhook Incoming XML',
      'uuid' => \Drupal::service('uuid')->generate(),
      'payload_url' => '',
      'type' => 'incoming',
      'events' => [],
      'content_type' => 'application/xml',
      'secret' => '',
      'status' => 1,
    ])->save();

    // Create an outgoing webhook.
    WebhookConfig::create([
      'id' => self::WEBHOOK_ID_OUTGOING_XML,
      'label' => 'Webhook Outgoing XML',
      'uuid' => \Drupal::service('uuid')->generate(),
      'payload_url' => Url::fromRoute('webhooks.webhook_receive', ['incoming_webhook_name' => self::WEBHOOK_ID_INCOMING_XML])->setAbsolute(TRUE)->toString(),
      'type' => 'outgoing',
      'events' => [],
      'content_type' => 'application/xml',
      'secret' => '',
      'status' => 1,
    ])->save();
  }

  /**
   * Test creation of incoming webhook.
   */
  public function testIncomingCreated() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_INCOMING);

    $this->assertInstanceOf(WebhookConfig::class, $webhook_config);
  }

  /**
   * Test creation of outgoing webhook.
   */
  public function testOutgoingCreated() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);

    $this->assertInstanceOf(WebhookConfig::class, $webhook_config);
  }

  /**
   * Test outgoing webhook.
   */
  public function testEventSend() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($this->payload);

    $this->webhookService->send($webhook_config, $webhook);

    $this->assertEqual($this->state->get('onWebhookSend'), TRUE);
  }

  /**
   * Test incoming webhook.
   */
  public function testEventReceive() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($this->payload);

    $this->webhookService->send($webhook_config, $webhook);

    $this->assertEqual($this->state->get('onWebhookReceive'), TRUE);
  }

  /**
   * Test webhook payload.
   */
  public function testPayload() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($this->payload);

    $this->webhookService->send($webhook_config, $webhook);
    /** @var \Drupal\webhooks\Webhook $webhook_received */
    $webhook_received = $this->state->get('onWebhookReceive_webhook');

    $this->assertEqual($webhook_received->getPayload(), $this->payload);
  }

  /**
   * Test webhook headers.
   */
  public function testHeaders() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($this->payload, $this->headers);

    $this->webhookService->send($webhook_config, $webhook);
    /** @var \Drupal\webhooks\Webhook $webhook_received */
    $webhook_received = $this->state->get('onWebhookReceive_webhook');
    $headers_received = $webhook_received->getHeaders();

    // Additional custom headers.
    $intersection = array_intersect($headers_received, $this->headers);
    $this->assertEqual($intersection, $this->headers);

    // Check for X-Drupal-Delivery header.
    $this->assertEqual($headers_received['x-drupal-delivery'], $webhook->getUuid());

    // Check for X-Drupal-Event header.
    $this->assertEqual($headers_received['x-drupal-event'], $webhook->getEvent());
  }

  /**
   * Test webhook signature.
   */
  public function testSignature() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING_VERIFIED);
    $webhook = new Webhook($this->payload);

    $this->webhookService->send($webhook_config, $webhook);

    // This succeeds if the webhook has been verified.
    $this->assertEqual($this->state->get('onWebhookReceive'), TRUE);
  }

  /**
   * Test webhook content type.
   */
  public function testContentTypeJson() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($this->payload);
    $webhook->setContentType('application/json');

    $this->webhookService->send($webhook_config, $webhook);

    /** @var \Drupal\webhooks\Webhook $webhook_received */
    $webhook_received = $this->state->get('onWebhookReceive_webhook');
    $this->assertEqual($webhook_received->getContentType(), 'application/json');
  }

  /**
   * Test webhook content type.
   * @group dev
   */
  public function testContentTypeXml() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING_XML);
    $webhook = new Webhook($this->payload);
    $webhook->setContentType('application/xml');

    $this->webhookService->send($webhook_config, $webhook);

    /** @var \Drupal\webhooks\Webhook $webhook_received */
    $webhook_received = $this->state->get('onWebhookReceive_webhook');
    $this->assertEqual($webhook_received->getContentType(), 'application/xml');
  }

}
