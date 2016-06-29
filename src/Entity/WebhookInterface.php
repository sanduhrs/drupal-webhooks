<?php

namespace Drupal\webhooks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Webhook entities.
 */
interface WebhookInterface extends ConfigEntityInterface {

  /**
   * @return string
   */
  public function getId();

  /**
   * @return string
   */
  public function getLabel();

  /**
   * @return string
   */
  public function getPayloadUrl();

  /**
   * @return string
   */
  public function getEvents();

  /**
   * @return string
   */
  public function getContentType();

  /**
   * @return boolean
   */
  public function isActive();

  /**
   * @return int
   */
  public function getLastUsage();

  /**
   * @return boolean
   */
  public function hasResponseOk();

  /**
   * @return string
   */
  public function getRefEntityType();

  /**
   * @return string
   */
  public function getRefEntityId();

  /**
   * @return string
   */
  public function getSecret();
}
