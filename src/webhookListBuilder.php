<?php

namespace Drupal\webhooks;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webhooks\Entity\webhook;

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
    $header['ref_entity_type'] = $this->t('Reference Entity Type');
    $header['entity_id'] = $this->t('Entity ID');
    $header['secret'] = $this->t('Secret');
    $header['content_type'] = $this->t('Content Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $webhook = new webhook();
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $webhook->isStatus();
    $row['payload_url'] = $webhook->getPayloadUrl();
    $row['last_usage'] = $webhook->getLastUsage();
    $row['events'] = $webhook->getEvents();
    $row['result'] = $webhook->isResult();
    $row['ref_entity_type'] = $webhook->getrefEntityType();
    $row['entity_id'] = $webhook->getEntityId();
    $row['secret'] = $webhook->getSecret();
    $row['content_type'] = $webhook->getContentType();
    return $row + parent::buildRow($entity);
  }

}
