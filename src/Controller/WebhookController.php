<?php

namespace Drupal\webhooks\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webhooks\WebhooksService;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Webhook.
 *
 * @package Drupal\webhooks\Controller
 */
class WebhookController extends ControllerBase {

  protected $webhooksService;

  protected $entityTypeManager;

  /**
   * WebhookController constructor.
   *
   * @param \Drupal\webhooks\WebhooksService $webhooks_service
   *   The Webhooks service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
      WebhooksService $webhooks_service,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->webhooksService = $webhooks_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webhooks.service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Webhooks receiver.
   *
   * @return Response
   *   Return 200 OK.
   */
  public function receive() {
    $this->webhooksService->receive();
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
    $webhooks_storage = $this->entityTypeManager->getStorage('webhook_config');
    /** @var \Drupal\webhooks\Entity\WebhookConfig $webhook_config */
    $webhook_config = $webhooks_storage->load($id);
    $webhook_config->setStatus(!$webhook_config->status());
    $webhook_config->save();
    return $this->redirect("entity.webhook_config.collection");
  }

}
