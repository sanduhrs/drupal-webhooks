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
   *   The current account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   A successful access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * Toggle the active state.
   *
   * @param mixed $id
   *    The id of the entity given by route url.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function toggleActive($id) {
    $webhooks_storage = \Drupal::entityTypeManager()->getStorage('webhook_config');
    $webhook_config = $webhooks_storage->load($id);
    $webhook_config->setStatus(!$webhook_config->status());
    $webhook_config->save();
    return $this->redirect("entity.webhook_config.collection");
  }

}
