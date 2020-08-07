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
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

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
    /** @var \Drupal\webhooks\WebhooksService $service */
    $service = \Drupal::service('webhooks.service');

    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook(['payload' => 'attribute']);
    $service->send($webhook_config, $webhook);

    $state = \Drupal::state();
    $this->assertEqual($state->get('onWebhookSend'), TRUE);
  }

  /**
   * Test outgoing webhook.
   */
  public function testEventReceive() {
    /** @var \Drupal\webhooks\WebhooksService $service */
    $service = \Drupal::service('webhooks.service');

    $webhook_config = WebhookConfig::load(self::WEBHOOK_ID_OUTGOING);
    $webhook = new Webhook(['payload' => 'attribute']);
    $service->send($webhook_config, $webhook);

    $state = \Drupal::state();
    $this->assertEqual($state->get('onWebhookReceive'), TRUE);
  }

}
