<?php

namespace Drupal\webhooks;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Webhook entities.
 */
class webhookListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Webhook');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    $header['payload_url'] = $this->t('Payload URL');
    $header['last_usage'] = $this->t('Last Usage');
    $header['events'] = $this->t('Events');
    $header['result'] = $this->t('Result');
    $header['entity_type'] = $this->t('Entity Type');
    $header['entity_id'] = $this->t('Entity ID');
    $header['secret'] = $this->t('Secret');
    $header['content_type'] = $this->t('Content Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $entity->status();
    $row['payload_url'] = $entity->payload_url();
    $row['last_usage'] = $entity->last_usage();
    $row['events'] = $entity->events();
    $row['result'] = $entity->result();
    $row['entity_type'] = $entity->entity_type();
    $row['entity_id'] = $entity->entity_id();
    $row['secret'] = $entity->secret();
    $row['content_type'] = $entity->content_type();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
