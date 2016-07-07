<?php

namespace Drupal\webhooks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Webhook entity.
 *
 * @ConfigEntityType(
 *   id = "webhook_config",
 *   label = @Translation("Webhook"),
 *   handlers = {
 *     "list_builder" = "Drupal\webhooks\WebhookConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webhooks\Form\WebhookConfigForm",
 *       "edit" = "Drupal\webhooks\Form\WebhookConfigForm",
 *       "delete" = "Drupal\webhooks\Form\WebhookConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webhooks\WebhookConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "webhook",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/webhook/{webhook_config}",
 *     "add-form" = "/admin/config/services/webhook/add",
 *     "edit-form" = "/admin/config/services/webhook/{webhook_config}/edit",
 *     "delete-form" = "/admin/config/services/webhook/{webhook_config}/delete",
 *     "collection" = "/admin/config/services/webhook"
 *   }
 * )
 */
class WebhookConfig extends ConfigEntityBase implements WebhookConfigInterface {

  /**
   * The Webhook ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Webhook label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Webhook Payload URL.
   *
   * @var string
   */
  protected $payload_url;

  /**
   * The Webhook events.
   *
   * @var array
   */
  protected $events;

  /**
   * The Webhook content type.
   *
   * @var string
   */
  protected $content_type;

  /**
   * The Webhook active.
   *
   * @var boolean
   */
  protected $active;

  /**
   * The Webhook last usage.
   *
   * @var integer
   */
  protected $last_usage;

  /**
   * The Webhook response_ok.
   *
   * @var boolean
   */
  protected $response_ok;

  /**
   * The Webhook reference entity type.
   *
   * @var string
   */
  protected $ref_entity_type;

  /**
   * The Webhook reference entity id.
   *
   * @var string
   */
  protected $ref_entity_id;

  /**
   * The Webhook secret.
   *
   * @var string
   */
  protected $secret;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if (isset($values['events']) && is_string($values['events'])) {
      $this->events = unserialize($values['events']);
    }
  }

  /**
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @return string
   */
  public function getPayloadUrl() {
    return $this->payload_url;
  }

  /**
   * @return string
   */
  public function getEvents() {
    return $this->events;
  }

  /**
   * @return string
   */
  public function getContentType() {
    return $this->content_type;
  }

  /**
   * @return boolean
   */
  public function isActive() {
    return $this->active;
  }

  /**
   * @param $state
   */
  public function setActive($state) {
    $this->active = $state;
  }

  /**
   * @return int
   */
  public function getLastUsage() {
    return $this->last_usage;
  }

  /**
   * @return boolean
   */
  public function hasResponseOk() {
    return $this->response_ok;
  }

  /**
   * @return string
   */
  public function getRefEntityType() {
    return $this->ref_entity_type;
  }

  /**
   * @return string
   */
  public function getRefEntityId() {
    return $this->ref_entity_id;
  }

  /**
   * @return string
   */
  public function getSecret() {
    return $this->secret;
  }

}
