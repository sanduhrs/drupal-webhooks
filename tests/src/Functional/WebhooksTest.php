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

  const WEBHOOK_SECRET = 'iepooleiDahF3eimeikooC2iep1ahqua';

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
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

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
    $webhook = new Webhook(['payload' => 'attribute']);

    $this->webhookService->send($webhook_config, $webhook);

    $this->assertEqual($this->state->get('onWebhookSend'), TRUE);
  }

  /**
   * Test incoming webhook.
   */
  public function testEventReceive() {
    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook(['payload' => 'attribute']);

    $this->webhookService->send($webhook_config, $webhook);

    $this->assertEqual($this->state->get('onWebhookReceive'), TRUE);
  }

  /**
   * Test webhook payload.
   */
  public function testPayload() {
    $payload = ['payload' => 'attribute'];

    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($payload);

    $this->webhookService->send($webhook_config, $webhook);
    /** @var \Drupal\webhooks\Webhook $webhook_received */
    $webhook_received = $this->state->get('onWebhookReceive_webhook');

    $this->assertEqual($webhook_received->getPayload(), $payload);
  }

  /**
   * Test webhook headers.
   */
  public function testHeaders() {
    $headers_custom = ['header-type' => 'header-value'];
    $payload = ['payload' => 'attribute'];

    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($payload, $headers_custom);

    $this->webhookService->send($webhook_config, $webhook);
    /** @var \Drupal\webhooks\Webhook $webhook_received */
    $webhook_received = $this->state->get('onWebhookReceive_webhook');
    $headers_received = $webhook_received->getHeaders();

    // Additional custom headers.
    $intersection = array_intersect($headers_received, $headers_custom);
    $this->assertEqual($intersection, $headers_custom);

    // Check for X-Drupal-Delivery header.
    $this->assertEqual($headers_received['x-drupal-delivery'], $webhook->getUuid());

    // Check for X-Drupal-Event header.
    $this->assertEqual($headers_received['x-drupal-event'], $webhook->getEvent());
  }

  /**
   * Test webhook signature.
   */
  public function testSignature() {
    $payload = ['payload' => 'attribute'];

    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook($payload);
    $webhook->setSecret(self::WEBHOOK_SECRET);

    $this->webhookService->send($webhook_config, $webhook);
    /** @var \Drupal\webhooks\Webhook $webhook_received */
    $webhook_received = $this->state->get('onWebhookReceive_webhook');

    // Verify webhook signature.
    $this->assertEqual($webhook_received->verify(self::WEBHOOK_SECRET), TRUE);
  }

}
