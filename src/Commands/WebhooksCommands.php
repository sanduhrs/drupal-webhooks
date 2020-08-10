<?php

namespace Drupal\webhooks\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webhooks\Entity\WebhookConfig;
use Drupal\webhooks\Webhook;
use Drush\Commands\DrushCommands;

/**
 * The webhooks Drush commandfile.
 */
class WebhooksCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Trigger all active webhooks configured for a given event.
   *
   * @param string $event
   *   Specify an event name to be triggered, e.g. 'entity:node:create',
   *   'entity:user:update' or 'entity:taxonomy_term:delete'.
   * @option payload
   *   JSON-encoded webhook payload for testing, e.g. '{"ping": "pong"}'.
   * @option headers
   *   JSON-encoded webhook headers for testing, e.g. '{"X-Custom": "Header"}'.
   * @option content_type
   *   Specify the content-type to send the webhook with, e.g. application/json
   *   or application/xml.
   * @usage webhooks-trigger entity:node:create
   *   Usage description
   *
   * @command webhooks:trigger
   * @aliases wt
   */
  // @phpcs:ignore Drupal.Commenting.FunctionComment.ParamMissingDefinition,Drupal.Arrays.Array.LongLineDeclaration,Drupal.Commenting.FunctionComment.Missing
  public function trigger($event, $options = ['payload' => '', 'headers' => '', 'event' => 'default', 'content_type' => 'application/json']) {
    $payload = (array) json_decode($options['payload'], TRUE);
    $headers = (array) json_decode($options['headers'], TRUE);

    $event = $event;
    $content_type = $options['content_type'];

    $webhook = new Webhook(
      $payload,
      $headers,
      $event,
      $content_type
    );

    /** @var \Drupal\webhooks\WebhooksService $webhooks_service */
    $webhooks_service = \Drupal::service('webhooks.service');
    // Trigger the webhook for all subscribers.
    $webhooks_service->triggerEvent($webhook, $event);
    $this->logger()->success($this->t('A webhook has been triggered with event @event and payload @payload', ['@event' => $event, '@payload' => $options['payload']]));
  }

  /**
   * List webhooks.
   *
   * @option type
   *   Filter by webhook type, e.g. incoming, outgoing.
   * @option status
   *   Filter by status, e.g. 0, 1.
   * @usage webhooks-list
   *   Usage description
   *
   * @command webhooks:list
   * @aliases wt
   * @field-labels
   *   display_name: Name
   *   machine_name: Machine Name
   *   type: Type
   *   status: Status
   * @default-fields display_name,machine_name,type,status
   * @aliases pml,pm-list
   * @filter-default-field display_name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The rows of fields object.
   */
  public function list($options = ['type' => '', 'status' => NULL]) {
    $query = \Drupal::entityQuery('webhook_config');

    if (isset($options['status'])) {
      $query->condition('status', $options['status']);
    }
    if ($options['type']) {
      $query->condition('type', $options['type']);
    }

    $ids = $query->execute();
    $webhooks_configs = WebhookConfig::loadMultiple($ids);
    /** @var \Drupal\webhooks\Entity\WebhookConfigInterface $webhooks_config */
    foreach ($webhooks_configs as $webhooks_config) {
      $rows[] = [
        'display_name' => $webhooks_config->label(),
        'machine_name' => $webhooks_config->id(),
        'type' => $this->t('@type', ['@type' => $webhooks_config->getType()]),
        'status' => $webhooks_config->status() ? $this->t('active') : $this->t('inactive'),
      ];
    }
    return new RowsOfFields((array) $rows);
  }

}
