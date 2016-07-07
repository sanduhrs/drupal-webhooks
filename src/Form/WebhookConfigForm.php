<?php

namespace Drupal\webhooks\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class webhookForm.
 *
 * @package Drupal\webhooks\Form
 */
class WebhookConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webhooks\Entity\Webhook $webhook */
    $webhook = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $webhook->label(),
      '#description' => $this->t("Label for the Webhook."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $webhook->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\webhooks\Entity\WebhookConfig::load',
      ),
      '#disabled' => !$webhook->isNew(),
    );

    $form['payload_url']= array(
      '#type' => 'textfield',
      '#title' => $this->t('Payload URL'),
      '#attributes' => array(
        'placeholder' => $this->t('http://example.com/post'),
      ),
      '#default_value' => $webhook->getPayloadUrl(),
      '#maxlength' => 255,
      '#description' => $this->t("Target URL for your payload."),
      '#required' => TRUE,
    );

    $form['secret'] = array(
      '#type' => 'password',
      '#attributes' => array(
        'placeholder' => $this->t('Secret'),
      ),
      '#title' => $this->t('Secret'),
      '#maxlength' => 255,
      '#description' => $this->t("Secret that the target website gave you."),
      '#default_value' => $webhook->getSecret(),
    );

    $form['active'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Active"),
      '#description' => $this->t("Shows if the webhook is active or not."),
      '#default_value' => $webhook->isActive(),
    );

    $form['content_type'] = array(
      '#type' => 'select',
      '#title' => $this->t("Content Type"),
      '#description' => $this->t("The Content Type of your webhook."),
      '#options' => [
        'json' => $this->t('application/json'),
        'xml' => $this->t('application/xml')
      ],
      '#default_value' => $webhook->getContentType(),
    );

    $form['events'] = array(
      '#type' => 'tableselect',
      '#header' => array('type' => 'Entity Type' , 'event' => 'Event'),
      '#description' => $this->t("The Events you want to send to the endpoint."),
      '#options' => [
        'entity:user:create' => ['type' => 'User' , 'event' => 'Create'],
        'entity:user:update' => ['type' => 'User' , 'event' => 'Update'],
        'entity:user:delete' => ['type' => 'User' , 'event' => 'Delete'],
        'entity:node:create' => ['type' => 'Node' , 'event' => 'Create'],
        'entity:node:update' => ['type' => 'Node' , 'event' => 'Update'],
        'entity:node:delete' => ['type' => 'Node' , 'event' => 'Delete'],
        'entity:comment:create' => ['type' => 'Comment' , 'event' => 'Create'],
        'entity:comment:update' => ['type' => 'Comment' , 'event' => 'Update'],
        'entity:comment:delete' => ['type' => 'Comment' , 'event' => 'Delete'],
      ],
    );
    // Check if the entity is not new, cause we get a warning otherwise.
    if (!$webhook->isNew()) {
      $form['events']['#default_value'] = $webhook->getEvents();
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webhooks\Entity\Webhook $webhook */
    $webhook = $this->entity;

    // Keep the old secret if no new one has been given.
    if (empty($form_state->getValue('secret'))) {
      $webhook->set('secret', $form['secret']['#default_value']);
    }

    // Serialize the events array before saving.
    $webhook->set('events', serialize($form_state->getValue('events')));

    $active = $webhook->save();

    switch ($active) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Webhook.', [
          '%label' => $webhook->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Webhook.', [
          '%label' => $webhook->label(),
        ]));
    }
    $form_state->setRedirectUrl($webhook->urlInfo('collection'));
  }

}