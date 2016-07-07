<?php

namespace Drupal\webhooks\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\webhooks\Event\WebhookEvents;
use Drupal\webhooks\Event\ReceiveEvent;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * Class Webhook.
 *
 * @package Drupal\webhooks\Controller
 */
class WebhookController extends ControllerBase {

  /**
   * Webhooks receiver.
   *
   * @return string
   *   Return Hello string.
   */
  public function receive() {
    /** @var \Drupal\webhooks\WebhookService $webhookService */
    $webhookService = \Drupal::service('webhooks.service');
    /** @var \Drupal\webhooks\Webhook $webhook */
    $webhook = $webhookService->receive();

    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher */
    $eventDispatcher = \Drupal::service('event_dispatcher');
    $eventDispatcher->dispatch(
      WebhookEvents::RECEIVE,
      new ReceiveEvent($webhook)
    );
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

}
