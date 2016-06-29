<?php

namespace Drupal\webhooks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Webhook entity.
 *
 * @ConfigEntityType(
 *   id = "webhook",
 *   label = @Translation("Webhook"),
 *   handlers = {
 *     "list_builder" = "Drupal\webhooks\WebhookListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webhooks\Form\WebhookForm",
 *       "edit" = "Drupal\webhooks\Form\WebhookForm",
 *       "delete" = "Drupal\webhooks\Form\WebhookDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webhooks\WebhookHtmlRouteProvider",
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
 *     "canonical" = "/admin/structure/webhook/{webhook}",
 *     "add-form" = "/admin/structure/webhook/add",
 *     "edit-form" = "/admin/structure/webhook/{webhook}/edit",
 *     "delete-form" = "/admin/structure/webhook/{webhook}/delete",
 *     "collection" = "/admin/structure/webhook"
 *   }
 * )
 */
class Webhook extends ConfigEntityBase implements WebhookInterface {

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
   * @var string
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
