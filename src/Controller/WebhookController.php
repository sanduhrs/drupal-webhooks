<?php

namespace Drupal\webhooks\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Class Webhook.
 *
 * @package Drupal\webhooks\Controller
 */
class WebhookController extends ControllerBase {

  /**
   * Webhooks receiver.
   *
   * @return Response
   *   Return 200 OK.
   */
  public function receive() {
    /** @var \Drupal\webhooks\WebhookService $webhookService */
    $webhookService = \Drupal::service('webhooks.service');
    $webhookService->receive();
    return new Response(200, [], 'OK');
  }

  /**
   * Access check callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * @param mixed $id
   *  The id of the entity given by route url.
   */
  public function toggleActive($id) {
    $webhooks_storage = \Drupal::entityTypeManager()->getStorage('webhook_config');
    $webhook_config = $webhooks_storage->load($id);
    $webhook_config->setActive(!$webhook_config->isActive());
    $webhook_config->set('events', serialize($webhook_config->getEvents()));
    $webhook_config->save();
    return $this->redirect("entity.webhook_config.collection");
  }

}
