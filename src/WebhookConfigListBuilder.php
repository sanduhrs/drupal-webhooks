<?php

namespace Drupal\webhooks;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Webhook entities.
 */
class WebhookConfigListBuilder extends ConfigEntityListBuilder {

  public function getOperations(EntityInterface $entity)
  {
    $operations = parent::getOperations($entity);
    $operations['toggle_active'] = array(
      'title' => $entity->isActive() ? t('Deactivate') : t('Activate'),
      'weight' => 0,
      'url' => \Drupal\Core\Url::fromRoute('webhooks.webhook_toggle_active', array('id' => $entity->id()))
    );
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['active'] = $this->t('Status');
    $header['label'] = $this->t('Webhook');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['active'] = $entity->isActive() ? $this->t('Active') : $this->t('Inactive');
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
