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
 *     "list_builder" = "Drupal\webhooks\webhookListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webhooks\Form\webhookForm",
 *       "edit" = "Drupal\webhooks\Form\webhookForm",
 *       "delete" = "Drupal\webhooks\Form\webhookDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webhooks\webhookHtmlRouteProvider",
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
class webhook extends ConfigEntityBase implements webhookInterface {

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
   * The Webhook status.
   *
   * @var boolean
   */
  protected $status;

  /**
   * The Webhook last usage.
   *
   * @var integer
   */
  protected $last_usage;

  /**
   * The Webhook result.
   *
   * @var boolean
   */
  protected $result;

  /**
   * The Webhook reference entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The Webhook reference entity id.
   *
   * @var string
   */
  protected $entity_id;

  /**
   * The Webhook secret.
   *
   * @var string
   */
  protected $secret;
}
