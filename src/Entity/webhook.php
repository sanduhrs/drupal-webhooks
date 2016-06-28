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

}
