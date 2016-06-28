<?php

namespace Drupal\webhooks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Webhook entities.
 */
interface webhookInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.
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
  public function isStatus();

  /**
   * @return int
   */
  public function getLastUsage();

  /**
   * @return boolean
   */
  public function isResult();

  /**
   * @return string
   */
  public function getrefEntityType();

  /**
   * @return string
   */
  public function getEntityId();

  /**
   * @return string
   */
  public function getSecret();
}
