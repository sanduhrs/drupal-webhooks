<?php

namespace Drupal\webhooks\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webhooks\Entity\WebhookConfig;

/**
 * Class WebhookConfigForm.
 *
 * @package Drupal\webhooks\Form
 */
class WebhookConfigForm extends EntityForm {

  protected $events = [];

  protected $entityHooks = [
    'create',
    'update',
    'delete',
  ];

  protected $systemHooks = [
    'cron',
    'file_download',
    'modules_installed',
    'user_cancel',
    'user_login',
    'user_logout',
    'cache_flush',
  ];

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webhooks\Entity\WebhookConfig $webhook_config */
    $webhook_config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $webhook_config->label(),
      '#description' => $this->t('Easily recognizable name for your webhook.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webhook_config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webhooks\Entity\WebhookConfig::load',
      ],
      '#disabled' => !$webhook_config->isNew(),
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'incoming' => $this->t('Incoming'),
        'outgoing' => $this->t('Outgoing'),
      ],
      '#default_value' => $webhook_config->getType() ? $webhook_config->getType() : 'outgoing',
      '#description' => $this->t('The type of webhook. <strong>Incoming webhooks</strong> receive HTTP events. <strong>Outgoing webhooks</strong> post new events to the configured URL.'),
      '#required' => TRUE,
      '#disabled' => !$webhook_config->isNew(),
    ];
    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t("Content Type"),
      '#description' => $this->t("The Content Type of your webhook."),
      '#options' => [
        WebhookConfig::CONTENT_TYPE_JSON => $this->t('application/json'),
        WebhookConfig::CONTENT_TYPE_XML => $this->t('application/xml'),
      ],
      '#default_value' => $webhook_config->getContentType(),
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => $this->t('Secret'),
      ],
      '#title' => $this->t('Secret'),
      '#maxlength' => 255,
      '#description' => $this->t('For <strong>incoming webhooks</strong> this secret is provided by the remote website. For <strong>outgoing webhooks</strong> this secret should be used for the incoming hook configuration on the remote website.'),
      '#default_value' => $webhook_config->getSecret(),
    ];
    $form['outgoing'] = [
      '#title' => $this->t('Outgoing Webhook Settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'expanded' => [
          ':input[name="type"]' => ['value' => 'outgoing'],
        ],
        'enabled' => [
          ':input[name="type"]' => ['value' => 'outgoing'],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => 'outgoing'],
        ],
        'collapsed' => [
          ':input[name="type"]' => ['value' => 'incoming'],
        ],
        'disabled' => [
          ':input[name="type"]' => ['value' => 'incoming'],
        ],
        'optional' => [
          ':input[name="type"]' => ['value' => 'incoming'],
        ],
      ],
    ];
    $form['outgoing']['payload_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Payload URL'),
      '#attributes' => [
        'placeholder' => $this->t('http://example.com/post'),
      ],
      '#default_value' => $webhook_config->getPayloadUrl(),
      '#maxlength' => 255,
      '#description' => $this->t('Target URL for your payload. Only used on <strong>outgoing webhooks</strong>.'),
    ];
    $form['outgoing']['events'] = [
      '#title' => $this->t('Enabled Events'),
      '#type' => 'tableselect',
      '#header' => [
        'type' => 'Hook / Event',
        'event' => 'Machine name'
      ],
      '#description' => $this->t("The Events you want to send to the endpoint."),
      '#options' => $this->eventOptions(),
      '#default_value' => $webhook_config->isNew() ? [] : $webhook_config->getEvents(),
    ];
    if ($webhook_config->getType() === 'incoming') {
      unset($form['outgoing']);
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Active"),
      '#description' => $this->t("Shows if the webhook is active or not."),
      '#default_value' => $webhook_config->isNew() ? TRUE : $webhook_config->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('type') == 'incoming') {
      // payload_url is required but not used on incoming webhooks.
      // Skipping the value entirely could break data model assumptions.
      $form_state->setValue('payload_url', 'http://example.com/webhook');
    }
    elseif ($form_state->isValueEmpty('payload_url')) {
      $form_state->setErrorByName('payload_url', $this->t('Outgoing webhooks require a Payload URL'));
    }

    if ($form_state->getValue('type') == 'outgoing' && $this->isEmptyList($form_state->getValue('events'))) {
      $form_state->setErrorByName('events', $this->t('Outgoing webhooks require one or more events to operate.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webhooks\Entity\WebhookConfig $webhook_config */
    $webhook_config = $this->entity;
    // Keep the old secret if no new one has been given.
    if (empty($form_state->getValue('secret'))) {
      $webhook_config->set('secret', $form['secret']['#default_value']);
    }
    $active = $webhook_config->save();

    switch ($active) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t(
          'Created the %label Webhook.',
          [
            '%label' => $webhook_config->label(),
          ]
        ));
        break;

      default:
        $this->messenger()->addStatus($this->t(
          'Saved the %label Webhook.',
          [
            '%label' => $webhook_config->label(),
          ]
        ));
    }
    /** @var \Drupal\Core\Url $url */
    $url = $webhook_config->toUrl('collection');
    $form_state->setRedirectUrl($url);
  }

  /**
   * Generate a list of available events.
   *
   * @return array
   *   Array of string identifiers for outgoing event options.
   */
  protected function eventOptions() {
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();

    $options = [];
    foreach ($entity_types as $entity_type => $definition) {
      if ($definition->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface')) {
        foreach ($this->entityHooks as $hook) {
          $options['entity:' . $entity_type . ':' . $hook] = [
            'type' => $this->t('Hook: %entity_label', ['%entity_label' => ucfirst($definition->getLabel())]),
            'event' => 'entity:' . $entity_type . ':' . $hook,
          ];
        }
      }
    }

    foreach ($this->systemHooks as $hook) {
      $options['hook:' . $hook] = [
        'type' => $this->t('Hook: %hook', ['%hook' => ucfirst($hook)]),
        'event' => 'system:' . $hook,
      ];
    }

    \Drupal::moduleHandler()->alter('webhooks_event_info', $options);
    return $options;
  }

  /**
   * Identifies if an array of form values is empty.
   *
   * FormState::isValueEmpty() does not handle tableselect or #tree submissions.
   *
   * @param array $list
   *   Array of key value pairs. keys are identifiers, values are 0 if empty or
   *   the same value as the key if checked on.
   *
   * @return bool
   *   TRUE if empty, FALSE otherwise.
   */
  protected function isEmptyList(array $list) {
    return count(array_filter($list)) == 0;
  }

}
